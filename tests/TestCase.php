<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Streeboga\LaravelApi\LaravelApiServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ActivitylogServiceProvider::class, LaravelApiServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Таблица activity_log нужна фикстуре, которая упражняет
        // LogsAuditActivity. Миграция пакета spatie лежит .stub-ом, поэтому
        // применяем её напрямую, а не через loadMigrationsFrom.
        $activityLog = require __DIR__.'/../vendor/spatie/laravel-activitylog/database/migrations/create_activity_log_table.php.stub';
        $activityLog->up();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
    }
}
