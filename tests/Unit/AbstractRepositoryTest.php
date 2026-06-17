<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Streeboga\LaravelApi\Repositories\AbstractRepository;
use Streeboga\LaravelApi\Tests\Fixtures\Models\TestBook;

uses(RefreshDatabase::class);

/**
 * @extends AbstractRepository<TestBook>
 */
final class TestBookRepository extends AbstractRepository
{
    protected function query(): Builder
    {
        return TestBook::query();
    }
}

beforeEach(function (): void {
    $this->repo = new TestBookRepository;
});

it('creates and finds by id', function () {
    $book = $this->repo->create(['key' => 'bk_one', 'title' => 'Dune']);

    expect($book)->toBeInstanceOf(TestBook::class);
    expect($this->repo->find($book->id))->not->toBeNull();
    expect($this->repo->find($book->id)->title)->toBe('Dune');
});

it('findByKey uses route key name', function () {
    $this->repo->create(['key' => 'bk_two', 'title' => 'Foundation']);

    $found = $this->repo->findByKey('bk_two');

    expect($found)->not->toBeNull();
    expect($found->title)->toBe('Foundation');
});

it('returns null for missing find/findByKey', function () {
    expect($this->repo->find(999))->toBeNull();
    expect($this->repo->findByKey('nope'))->toBeNull();
});

it('findOrFail throws on missing', function () {
    $this->repo->findOrFail(999);
})->throws(ModelNotFoundException::class);

it('exists returns correct boolean', function () {
    $book = $this->repo->create(['key' => 'bk_three', 'title' => 'Hyperion']);

    expect($this->repo->exists($book->id))->toBeTrue();
    expect($this->repo->exists(99999))->toBeFalse();
});

it('updates model', function () {
    $book = $this->repo->create(['key' => 'bk_upd', 'title' => 'Old']);

    $updated = $this->repo->update($book, ['title' => 'New']);

    expect($updated->title)->toBe('New');
});

it('deletes model', function () {
    $book = $this->repo->create(['key' => 'bk_del', 'title' => 'Goner']);

    expect($this->repo->delete($book))->toBeTrue();
    expect($this->repo->find($book->id))->toBeNull();
});
