<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Streeboga\LaravelApi\Models\Concerns\HasPrefixedUlid;

/**
 * @property string $id
 * @property string $title
 */
final class TestUlidBook extends Model
{
    use HasPrefixedUlid;

    protected $table = 'ulid_books';

    /** @var list<string> */
    protected $fillable = ['title'];

    public $timestamps = false;

    public function ulidPrefix(): string
    {
        return 'bk';
    }
}
