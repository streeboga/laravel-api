<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

final class JsonApiExceptionRenderer
{
    /**
     * Convert a supported exception into a JSON:API error response,
     * or return null if this renderer does not handle the exception.
     */
    public function render(Throwable $exception): ?JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return $this->renderValidation($exception);
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->renderModelNotFound($exception);
        }

        if ($exception instanceof AuthenticationException) {
            return $this->renderAuthentication($exception);
        }

        if ($exception instanceof AuthorizationException) {
            return $this->renderAuthorization($exception);
        }

        if ($exception instanceof DomainException) {
            return $this->renderDomain($exception);
        }

        return null;
    }

    private function renderValidation(ValidationException $e): JsonResponse
    {
        $errors = [];

        /** @var array<string, array<int, string>> $messages */
        $messages = $e->errors();

        foreach ($messages as $pointer => $fieldMessages) {
            foreach ($fieldMessages as $message) {
                $errors[] = [
                    'status' => (string) $e->status,
                    'code' => 'validation_error',
                    'title' => 'Validation Failed',
                    'detail' => $message,
                    'source' => ['pointer' => '/data/attributes/'.$pointer],
                ];
            }
        }

        return $this->jsonResponse($errors, $e->status);
    }

    /**
     * @param  ModelNotFoundException<Model>  $e
     */
    private function renderModelNotFound(ModelNotFoundException $e): JsonResponse
    {
        return $this->jsonResponse([[
            'status' => '404',
            'code' => 'not_found',
            'title' => 'Resource Not Found',
            'detail' => $e->getMessage() !== '' ? $e->getMessage() : 'The requested resource was not found.',
        ]], 404);
    }

    private function renderAuthentication(AuthenticationException $e): JsonResponse
    {
        return $this->jsonResponse([[
            'status' => '401',
            'code' => 'unauthenticated',
            'title' => 'Unauthenticated',
            'detail' => $e->getMessage() !== '' ? $e->getMessage() : 'Authentication is required.',
        ]], 401);
    }

    private function renderAuthorization(AuthorizationException $e): JsonResponse
    {
        return $this->jsonResponse([[
            'status' => '403',
            'code' => 'forbidden',
            'title' => 'Forbidden',
            'detail' => $e->getMessage() !== '' ? $e->getMessage() : 'This action is unauthorized.',
        ]], 403);
    }

    private function renderDomain(DomainException $e): JsonResponse
    {
        return $this->jsonResponse([[
            'status' => (string) $e->statusCode(),
            'code' => $e->errorCode(),
            'title' => $e->title(),
            'detail' => $e->getMessage(),
        ]], $e->statusCode());
    }

    /**
     * @param  array<int, array<string, mixed>>  $errors
     */
    private function jsonResponse(array $errors, int $status): JsonResponse
    {
        return new JsonResponse(['errors' => $errors], $status, [
            'Content-Type' => 'application/vnd.api+json',
        ]);
    }
}
