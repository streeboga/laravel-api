<?php

declare(strict_types=1);

use Streeboga\LaravelApi\Services\AbstractService;

it('wraps callback in DB transaction', function () {
    $service = new class extends AbstractService
    {
        public function run(Closure $cb): mixed
        {
            return $this->withinTransaction($cb);
        }
    };

    expect($service->run(fn () => 'result'))->toBe('result');
});

it('rolls back on exception', function () {
    $service = new class extends AbstractService
    {
        public function run(Closure $cb): mixed
        {
            return $this->withinTransaction($cb);
        }
    };

    expect(fn () => $service->run(fn () => throw new RuntimeException('boom')))
        ->toThrow(RuntimeException::class, 'boom');
});
