<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Builders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\QueryBuilder as SpatieQueryBuilder;

/**
 * Base for listing query builders that page through JSON:API.
 *
 * Holds the cross-cutting paging mechanics that otherwise get copy-pasted into
 * every `<Entity>QueryBuilder`: the hard per-page ceiling, the 1..MAX clamp and
 * the JSON:API `page[number]` page-name. Concrete builders only declare their
 * own static factory (`make()` / `from()` / `forUser()`), their allowed
 * filters/sorts and an optional `DEFAULT_PER_PAGE` override.
 *
 * Deliberately separate from {@see AbstractQueryBuilder}: that one builds a
 * Spatie query FROM a Request and is consumed elsewhere, this one wraps an
 * already-built query and only owns pagination. Merging them would change the
 * contract of a class that already has consumers.
 *
 * @template TModel of Model
 */
abstract class AbstractPaginatedQueryBuilder
{
    /** Hard ceiling on page size, regardless of the requested `page[size]`. */
    protected const MAX_PER_PAGE = 100;

    /** Fallback page size when the request omits `page[size]`. */
    protected const DEFAULT_PER_PAGE = 15;

    /**
     * JSON:API pagination page-number parameter, in dot-notation.
     *
     * The SPA sends `?page[number]=2`, which PHP parses to
     * `['page' => ['number' => 2]]`. Laravel's paginator resolves the page via
     * `data_get($request, $pageName)`, which only understands dot-notation — so
     * the pageName MUST be `page.number`, not the literal `page[number]` (that
     * resolves to null and pins every request to page 1).
     */
    protected const PAGE_NAME = 'page.number';

    /**
     * Concrete builders construct via their own static factory, never directly.
     *
     * @param  SpatieQueryBuilder<TModel>  $query
     */
    protected function __construct(
        protected readonly SpatieQueryBuilder $query,
    ) {}

    /**
     * Paginate the built query, clamping the page size to [1, MAX_PER_PAGE].
     *
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage): LengthAwarePaginator
    {
        $size = min(max($perPage, 1), self::intConst(static::MAX_PER_PAGE, 100));

        return $this->query->paginate(
            perPage: $size,
            pageName: is_string(static::PAGE_NAME) ? static::PAGE_NAME : 'page.number',
        );
    }

    /**
     * The module's default page size (override `DEFAULT_PER_PAGE` to change it).
     */
    public static function defaultPerPage(): int
    {
        return self::intConst(static::DEFAULT_PER_PAGE, 15);
    }

    /**
     * Value of a child-declared constant, with a fallback.
     *
     * The constants above are deliberately left UNTYPED: consumers override
     * `protected const DEFAULT_PER_PAGE = 20;` without a type, and PHP 8.3
     * forbids redeclaring a typed parent constant as untyped — that is a fatal
     * error at class-load time, and it fails silently. Hence the type is
     * checked here rather than in the declaration.
     */
    private static function intConst(mixed $value, int $fallback): int
    {
        return is_int($value) ? $value : $fallback;
    }
}
