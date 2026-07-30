<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\QueryBuilder\QueryBuilder;
use Streeboga\LaravelApi\Builders\AbstractPaginatedQueryBuilder;
use Streeboga\LaravelApi\Tests\Fixtures\Models\TestBook;

uses(RefreshDatabase::class);

/** Builder that keeps the defaults. */
final class DefaultBookPaginator extends AbstractPaginatedQueryBuilder
{
    public static function make(): self
    {
        return new self(QueryBuilder::for(TestBook::query()));
    }
}

/**
 * Builder that overrides the page size the way consumers do — WITHOUT a type on
 * the constant. PHP forbids redeclaring a typed parent constant as untyped, so
 * this class failing to load is exactly the regression the base guards against.
 */
final class SmallPageBookPaginator extends AbstractPaginatedQueryBuilder
{
    protected const DEFAULT_PER_PAGE = 2;

    protected const MAX_PER_PAGE = 3;

    public static function make(): self
    {
        return new self(QueryBuilder::for(TestBook::query()));
    }
}

function seedBooks(int $count): void
{
    foreach (range(1, $count) as $i) {
        TestBook::query()->create(['key' => 'bk_'.$i, 'title' => 'Book '.$i]);
    }
}

it('exposes the default page size and lets a child override it untyped', function (): void {
    expect(DefaultBookPaginator::defaultPerPage())->toBe(15)
        ->and(SmallPageBookPaginator::defaultPerPage())->toBe(2);
});

it('clamps the page size to the child ceiling', function (): void {
    seedBooks(5);

    // Запрошено больше потолка — отдаём потолок, а не запрошенное.
    expect(SmallPageBookPaginator::make()->paginate(100)->perPage())->toBe(3);
});

it('never paginates by less than one row', function (): void {
    seedBooks(2);

    // Нуль и отрицательное значение приходят из query-строки; они не должны
    // превращаться в деление на ноль внутри пагинатора.
    expect(DefaultBookPaginator::make()->paginate(0)->perPage())->toBe(1)
        ->and(DefaultBookPaginator::make()->paginate(-5)->perPage())->toBe(1);
});

it('resolves the requested page through the dot-notation page name', function (): void {
    seedBooks(5);

    // JSON:API присылает ?page[number]=2 → ['page' => ['number' => 2]].
    // Пагинатор читает его через data_get, который понимает только точку, поэтому
    // pageName обязан быть `page.number`: с литералом `page[number]` каждый
    // запрос молча возвращал бы первую страницу.
    request()->merge(['page' => ['number' => 2]]);

    $page = SmallPageBookPaginator::make()->paginate(2);

    expect($page->currentPage())->toBe(2)
        ->and($page->perPage())->toBe(2)
        ->and($page->total())->toBe(5);
});
