<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Streeboga\LaravelApi\Http\Controllers\AbstractApiController;

it('provides created() helper returning 201 with Location header', function () {
    $controller = new class extends AbstractApiController
    {
        /**
         * @param  array<string, mixed>  $data
         */
        public function callCreated(string $location, array $data): JsonResponse
        {
            return $this->created($location, $data);
        }
    };

    $response = $controller->callCreated('/resources/1', ['id' => 1]);

    expect($response->getStatusCode())->toBe(201)
        ->and($response->headers->get('Location'))->toBe('/resources/1')
        ->and($response->getData(true))->toBe(['id' => 1]);
});

it('provides noContent() helper returning 204', function () {
    $controller = new class extends AbstractApiController
    {
        public function callNoContent(): Response
        {
            return $this->noContent();
        }
    };

    expect($controller->callNoContent()->getStatusCode())->toBe(204);
});
