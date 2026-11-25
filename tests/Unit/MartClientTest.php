<?php

namespace Tests\Unit;

use App\Services\Mart\MartClient;
use App\Services\Mart\MartException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MartClientTest extends TestCase
{
    public function test_unwraps_the_envelope_and_sends_the_key(): void
    {
        Http::fake(['*' => Http::response(['data' => ['a' => 1], 'meta' => ['b' => 2]])]);

        $this->assertSame(['data' => ['a' => 1], 'meta' => ['b' => 2]], app(MartClient::class)->get('account'));
        Http::assertSent(fn ($r) => $r->url() === 'https://mart.test/api/v1/account'
            && $r->hasHeader('Authorization', 'Bearer mk_test_fake'));
    }

    public function test_tells_refusal_rate_limit_and_unknown_apart(): void
    {
        Http::fake([
            '*/refused' => Http::response(['error' => ['code' => 'invalid_state']], 422),
            '*/limited' => Http::response(['error' => ['code' => 'rate_limit_exceeded']], 429, ['Retry-After' => '7']),
            '*/broken' => Http::response('oops', 502),
            '*/down' => fn () => throw new ConnectionException('timeout'),
        ]);

        $kind = function (string $path): MartException {
            try {
                app(MartClient::class)->get($path);
            } catch (MartException $e) {
                return $e;
            }
            $this->fail("$path did not throw");
        };

        $this->assertSame(MartException::REFUSED, $kind('refused')->kind);
        $this->assertSame('invalid_state', $kind('refused')->errorCode);
        $this->assertSame(7, $kind('limited')->retryAfter);
        $this->assertSame(MartException::UNKNOWN, $kind('broken')->kind);
        $this->assertSame(MartException::UNKNOWN, $kind('down')->kind);
    }
}
