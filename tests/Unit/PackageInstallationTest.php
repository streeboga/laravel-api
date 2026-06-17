<?php

declare(strict_types=1);
use Streeboga\LaravelApi\LaravelApiServiceProvider;

it('registers service provider', function () {
    expect(app()->getLoadedProviders())
        ->toHaveKey(LaravelApiServiceProvider::class);
});
