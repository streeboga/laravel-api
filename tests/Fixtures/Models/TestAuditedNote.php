<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Streeboga\LaravelApi\Models\Concerns\LogsAuditActivity;

/**
 * Fixture exercising {@see LogsAuditActivity}.
 *
 * @property int $id
 * @property string $body
 * @property int|null $user_id
 */
final class TestAuditedNote extends Model
{
    use LogsAuditActivity;

    protected $table = 'audited_notes';

    /** @var list<string> */
    protected $fillable = ['body', 'user_id'];

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['body'])->useLogName('note');
    }
}
