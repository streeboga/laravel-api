<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Tests\Fixtures\Enums;

use Streeboga\LaravelApi\Contracts\Enums\HasColor;
use Streeboga\LaravelApi\Contracts\Enums\HasIcon;
use Streeboga\LaravelApi\Contracts\Enums\HasLabel;

enum TestBookStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Published => 'Опубликовано',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'green',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Published => 'heroicon-o-check',
        };
    }
}
