<?php

namespace App\Services\Mart;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

/** The only code that calls Mart. See specs/001-reseller-storefront/contracts/mart-api.md. */
class MartClient
{
    /** @return array{data: array, meta: array} */
    public function get(string $path, array $query = [], int $timeout = 60): array
    {
        return $this->send(fn (PendingRequest $http) => $http->get($path, $query), $timeout);
    }

    /**
     * For background syncs: waits out a rate limit or a short outage, three tries in all.
     *
     * @return array{data: array, meta: array}
     */
    public function getPatiently(string $path, array $query = [], int $timeout = 120): array
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->get($path, $query, $timeout);
            } catch (MartException $e) {
                if (! $e->retryable() || $attempt >= 3 || $e->retryAfter > 120) {
                    throw $e;
                }
                Sleep::for(max($e->retryAfter, 5))->seconds();
            }
        }
    }

    /** @return array{data: array, meta: array} */
    public function post(string $path, array $body = [], int $timeout = 60): array
    {
        return $this->send(fn (PendingRequest $http) => $http->post($path, $body), $timeout);
    }

    private function send(callable $call, int $timeout): array
    {
        $http = Http::baseUrl(rtrim((string) config('services.mart.base_url'), '/').'/api/v1/')
            ->withToken((string) config('services.mart.key'))
            ->acceptJson()
            ->timeout($timeout);

        try {
            /** @var Response $response */
            $response = $call($http);
        } catch (ConnectionException) {
            throw new MartException(MartException::UNKNOWN, 'connection_failed', 'Mart did not answer.');
        }

        $json = $response->json();

        if ($response->successful() && is_array($json) && is_array($json['data'] ?? null)) {
            return ['data' => $json['data'], 'meta' => $json['meta'] ?? []];
        }

        $kind = match (true) {
            $response->status() === 429 => MartException::RATE_LIMITED,
            $response->clientError() => MartException::REFUSED,
            default => MartException::UNKNOWN,
        };

        throw new MartException(
            $kind,
            (string) ($json['error']['code'] ?? 'unexpected_response'),
            (string) ($json['message'] ?? 'Unexpected response from Mart.'),
            $response->status(),
            (array) ($json['error']['details'] ?? []),
            (int) $response->header('Retry-After'),
        );
    }
}
