<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

abstract class AbstractApiController extends Controller
{
    protected function created(string $location, mixed $data): JsonResponse
    {
        $response = new JsonResponse($data, Response::HTTP_CREATED);
        $response->headers->set('Location', $location);

        return $response;
    }

    protected function noContent(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
