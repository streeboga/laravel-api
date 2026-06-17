<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Contracts\Enums;

interface HasLabel
{
    public function label(): string;
}
