<?php

namespace App\Services;

use RuntimeException;

/** A message that is safe to show to the person who triggered the action. */
class CheckoutException extends RuntimeException
{
    public function __construct(string $message, public readonly array $figures = [])
    {
        parent::__construct($message);
    }
}
