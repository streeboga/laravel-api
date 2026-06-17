<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Exceptions;

use RuntimeException;

/**
 * Marker class for domain-level exceptions that should be converted
 * to JSON:API error responses by the JsonApiExceptionRenderer.
 *
 * Subclasses should set:
 *  - HTTP status via `$statusCode`,
 *  - machine-readable `$errorCode` for API clients,
 *  - human-readable `$title`.
 */
abstract class DomainException extends RuntimeException
{
    protected int $statusCode = 422;

    protected string $errorCode = 'domain_error';

    protected string $title = 'Domain Error';

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function title(): string
    {
        return $this->title;
    }
}
