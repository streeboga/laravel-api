<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Streeboga\LaravelApi\Tests\Fixtures\Models\TestUlidBook;

uses(RefreshDatabase::class);

it('generates prefix_ULID primary key on creating', function () {
    $book = TestUlidBook::query()->create(['title' => 'Dune']);

    expect($book->id)->toMatch('/^bk_[0-9a-z]{26}$/');
});

it('does not override explicit id', function () {
    $book = new TestUlidBook(['title' => 'Foundation']);
    $book->id = 'bk_custom123';
    $book->save();

    expect($book->fresh()->id)->toBe('bk_custom123');
});

it('sets non-incrementing string key type', function () {
    $book = new TestUlidBook;

    expect($book->getIncrementing())->toBeFalse()
        ->and($book->getKeyType())->toBe('string');
});
