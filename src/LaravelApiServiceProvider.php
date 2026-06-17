<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi;

use Illuminate\Support\ServiceProvider;
use Streeboga\LaravelApi\Console\SyncSkillsCommand;

final class LaravelApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncSkillsCommand::class,
            ]);
        }
    }
}
