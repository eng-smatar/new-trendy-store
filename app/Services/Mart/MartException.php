<?php

namespace App\Services\Mart;

use RuntimeException;

class MartException extends RuntimeException
{
    /** Mart said no; resending the same call will not help. */
    public const REFUSED = 'refused';

    public const RATE_LIMITED = 'rate_limited';

    /** Timeout or 5xx: Mart may or may not have acted. */
    public const UNKNOWN = 'unknown';

    public function __construct(
        public readonly string $kind,
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 0,
        public readonly array $details = [],
        public readonly int $retryAfter = 0,
    ) {
        parent::__construct($message);
    }

    public function retryable(): bool
    {
        return $this->kind !== self::REFUSED;
    }
}
