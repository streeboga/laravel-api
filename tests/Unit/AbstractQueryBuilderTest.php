<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Streeboga\LaravelApi\Builders\AbstractQueryBuilder;
use Streeboga\LaravelApi\Tests\Fixtures\Models\TestBook;

uses(RefreshDatabase::class);

final class TestBookQueryBuilder extends AbstractQueryBuilder
{
    /** @var array<int, string> */
    protected array $allowedFilters = ['title'];

    /** @var array<int, string> */
    protected array $allowedSorts = ['title'];

    protected ?string $defaultSort = 'title';

    protected function baseQuery(): Builder
    {
        return TestBook::query();
    }
}

it('applies allowed filter from request', function () {
    TestBook::query()->create(['key' => 'bk_a', 'title' => 'Dune']);
    TestBook::query()->create(['key' => 'bk_b', 'title' => 'Foundation']);

    $request = Request::create('/', 'GET', ['filter' => ['title' => 'Dune']]);

    $results = (new TestBookQueryBuilder)->fromRequest($request)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Dune');
});

it('applies default sort when no sort provided', function () {
    TestBook::query()->create(['key' => 'bk_c', 'title' => 'B']);
    TestBook::query()->create(['key' => 'bk_d', 'title' => 'A']);

    $results = (new TestBookQueryBuilder)->fromRequest(Request::create('/', 'GET'))->get();

    expect($results->pluck('title')->all())->toBe(['A', 'B']);
});
