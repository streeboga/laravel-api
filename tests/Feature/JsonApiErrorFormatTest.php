<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Streeboga\LaravelApi\Exceptions\ConflictException;
use Streeboga\LaravelApi\Exceptions\JsonApiExceptionRenderer;
use Streeboga\LaravelApi\Exceptions\NotFoundException;

beforeEach(function (): void {
    $renderer = new JsonApiExceptionRenderer;

    app()->singleton('exception.renderer.test', fn () => $renderer);

    $this->app['router']->any('/test-exception/{kind}', function (string $kind) use ($renderer) {
        $exception = match ($kind) {
            'validation' => ValidationException::withMessages(['title' => ['The title field is required.']]),
            'not-found-model' => new ModelNotFoundException('Book not found.'),
            'auth' => new AuthenticationException('Authentication is required.'),
            'forbidden' => new AuthorizationException('Nope.'),
            'domain-not-found' => new NotFoundException('Custom domain not found.'),
            'domain-conflict' => new ConflictException('Duplicate key.'),
            default => throw new RuntimeException('Unknown kind.'),
        };

        return $renderer->render($exception);
    });
});

it('renders ValidationException in JSON:API format', function () {
    $response = $this->get('/test-exception/validation');

    $response->assertStatus(422);
    $response->assertHeader('Content-Type', 'application/vnd.api+json');

    $body = $response->json();

    expect($body)->toHaveKey('errors')
        ->and($body['errors'][0])->toMatchArray([
            'status' => '422',
            'code' => 'validation_error',
            'title' => 'Validation Failed',
            'detail' => 'The title field is required.',
        ])
        ->and($body['errors'][0]['source']['pointer'])->toBe('/data/attributes/title');
});

it('renders ModelNotFoundException as 404', function () {
    $response = $this->get('/test-exception/not-found-model');

    $response->assertStatus(404);
    expect($response->json('errors.0.code'))->toBe('not_found');
});

it('renders AuthenticationException as 401', function () {
    $response = $this->get('/test-exception/auth');

    $response->assertStatus(401);
    expect($response->json('errors.0.code'))->toBe('unauthenticated');
});

it('renders AuthorizationException as 403', function () {
    $response = $this->get('/test-exception/forbidden');

    $response->assertStatus(403);
    expect($response->json('errors.0.code'))->toBe('forbidden');
});

it('renders NotFoundException with custom status/code/title', function () {
    $response = $this->get('/test-exception/domain-not-found');

    $response->assertStatus(404);
    expect($response->json('errors.0'))->toMatchArray([
        'status' => '404',
        'code' => 'not_found',
        'title' => 'Resource Not Found',
        'detail' => 'Custom domain not found.',
    ]);
});

it('renders ConflictException as 409', function () {
    $response = $this->get('/test-exception/domain-conflict');

    $response->assertStatus(409);
    expect($response->json('errors.0'))->toMatchArray([
        'status' => '409',
        'code' => 'conflict',
        'title' => 'Conflict',
        'detail' => 'Duplicate key.',
    ]);
});

it('returns null for unsupported exception', function () {
    $renderer = new JsonApiExceptionRenderer;

    expect($renderer->render(new RuntimeException('boom')))->toBeNull();
});
