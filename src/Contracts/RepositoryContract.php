<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface RepositoryContract
{
    /**
     * @return TModel|null
     */
    public function find(string|int $id): ?Model;

    /**
     * @return TModel
     */
    public function findOrFail(string|int $id): Model;

    /**
     * @return TModel|null
     */
    public function findByKey(string $key): ?Model;

    public function exists(string|int $id): bool;

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model;

    /**
     * @param  TModel  $model
     */
    public function delete(Model $model): bool;
}
