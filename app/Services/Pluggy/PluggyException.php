<?php

namespace App\Services\Pluggy;

use RuntimeException;

/**
 * A failed Pluggy call. Carries only what is safe to log or show: never
 * credentials, the API key or the response body.
 */
class PluggyException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?string $codeDescription = null,
        public readonly ?string $requestId = null,
    ) {
        parent::__construct($message);
    }
}
