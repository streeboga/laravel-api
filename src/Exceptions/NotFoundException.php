<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Exceptions;

final class NotFoundException extends DomainException
{
    protected int $statusCode = 404;

    protected string $errorCode = 'not_found';

    protected string $title = 'Resource Not Found';
}
