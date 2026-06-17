<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Services;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

abstract class AbstractService
{
    /**
     * @template TReturn
     *
     * @param  Closure(Connection): TReturn  $callback
     * @param  int<1, max>  $attempts
     * @return TReturn
     */
    protected function withinTransaction(Closure $callback, int $attempts = 1): mixed
    {
        return DB::transaction($callback, $attempts);
    }
}
