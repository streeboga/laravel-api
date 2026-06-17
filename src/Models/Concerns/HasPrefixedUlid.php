<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generates a prefix-scoped ULID as the primary key on `creating`.
 *
 * Example key: `bk_01hzf2xq3k4vp8r2n9e5t7b1my`.
 *
 * Consumer classes must:
 *  - extend `Illuminate\Database\Eloquent\Model`,
 *  - implement `ulidPrefix(): string`.
 *
 * @mixin Model
 */
trait HasPrefixedUlid
{
    public static function bootHasPrefixedUlid(): void
    {
        static::creating(static function (Model $model): void {
            if ($model instanceof self) {
                $model->assignPrefixedUlidIfMissing();
            }
        });
    }

    abstract public function ulidPrefix(): string;

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    protected function assignPrefixedUlidIfMissing(): void
    {
        $keyName = $this->getKeyName();
        $currentValue = $this->getAttribute($keyName);

        if ($currentValue === null || $currentValue === '') {
            $this->setAttribute($keyName, $this->ulidPrefix().'_'.strtolower((string) Str::ulid()));
        }
    }
}
