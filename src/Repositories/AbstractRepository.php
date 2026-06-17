<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Streeboga\LaravelApi\Contracts\RepositoryContract;

/**
 * @template TModel of Model
 *
 * @implements RepositoryContract<TModel>
 */
abstract class AbstractRepository implements RepositoryContract
{
    /**
     * Produce a fresh builder for the managed model.
     *
     * Subclasses return `Model::query()` which phpstan infers as
     * `Builder<TModel>` via the concrete model's `static` return type.
     *
     * @return Builder<TModel>
     */
    abstract protected function query(): Builder;

    public function find(string|int $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findOrFail(string|int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function findByKey(string $key): ?Model
    {
        $key_name = $this->query()->getModel()->getRouteKeyName();

        return $this->query()->where($key_name, $key)->first();
    }

    public function exists(string|int $id): bool
    {
        return $this->query()->whereKey($id)->first() !== null;
    }

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        $fresh = $model->fresh();

        if ($fresh === null) {
            throw new \RuntimeException('Failed to reload model after update.');
        }

        return $fresh;
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
