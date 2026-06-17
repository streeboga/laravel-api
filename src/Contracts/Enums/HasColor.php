<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Contracts\Enums;

interface HasColor
{
    public function color(): string;
}
