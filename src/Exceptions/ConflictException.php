<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Exceptions;

final class ConflictException extends DomainException
{
    protected int $statusCode = 409;

    protected string $errorCode = 'conflict';

    protected string $title = 'Conflict';
}
