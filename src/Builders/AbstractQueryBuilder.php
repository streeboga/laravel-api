<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Builders;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

abstract class AbstractQueryBuilder
{
    /** @var array<int, string> */
    protected array $allowedFilters = [];

    /** @var array<int, string> */
    protected array $allowedSorts = [];

    /** @var array<int, string> */
    protected array $allowedIncludes = [];

    protected ?string $defaultSort = null;

    /**
     * @return EloquentBuilder<Model>
     */
    abstract protected function baseQuery(): EloquentBuilder;

    /**
     * @return QueryBuilder<Model>
     */
    public function fromRequest(Request $request): QueryBuilder
    {
        $qb = QueryBuilder::for($this->baseQuery(), $request)
            ->allowedFilters(...$this->allowedFilters)
            ->allowedSorts(...$this->allowedSorts)
            ->allowedIncludes(...$this->allowedIncludes);

        if ($this->defaultSort !== null) {
            $qb->defaultSort($this->defaultSort);
        }

        return $qb;
    }
}
