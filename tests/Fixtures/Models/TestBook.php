<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $title
 */
final class TestBook extends Model
{
    protected $table = 'books';

    /** @var list<string> */
    protected $fillable = ['key', 'title'];

    public $timestamps = false;

    public function getRouteKeyName(): string
    {
        return 'key';
    }
}
