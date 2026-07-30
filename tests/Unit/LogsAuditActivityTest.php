<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Streeboga\LaravelApi\Tests\Fixtures\Models\TestAuditedNote;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| LogsAuditActivity
|--------------------------------------------------------------------------
|
| Единственное, чем этот трейт отличается от spatie/LogsActivity — штамп
| владельца в properties. Ради него он и существует: читающий API фильтрует
| записи по владельцу, не джойнясь к полиморфному subject.
|
*/

it('stamps the owner id into the activity properties', function (): void {
    TestAuditedNote::query()->create(['body' => 'first', 'user_id' => 42]);

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->getProperty('user_id'))->toBe(42);
});

it('omits the owner property when the model has no owner', function (): void {
    TestAuditedNote::query()->create(['body' => 'orphan', 'user_id' => null]);

    $activity = Activity::query()->latest('id')->first();

    // Ключа быть не должно вовсе, а не null: пустой владелец и «владельца нет»
    // читающему API различать не нужно, а лишний ключ он бы принял за значение.
    expect($activity->getProperty('user_id'))->toBeNull();
});

it('honours logOnly and records the changed attribute', function (): void {
    $note = TestAuditedNote::query()->create(['body' => 'before', 'user_id' => 7]);

    $note->update(['body' => 'after']);

    $activity = Activity::query()->latest('id')->first();

    expect($activity->description)->toBe('updated')
        ->and($activity->attribute_changes['attributes']['body'])->toBe('after')
        ->and($activity->getProperty('user_id'))->toBe(7);
});

it('uses the log name declared by the model', function (): void {
    TestAuditedNote::query()->create(['body' => 'named', 'user_id' => 1]);

    expect(Activity::query()->latest('id')->first()->log_name)->toBe('note');
});
