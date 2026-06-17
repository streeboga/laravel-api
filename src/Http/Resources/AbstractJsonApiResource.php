<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use TiMacDonald\JsonApi\JsonApiResource;

abstract class AbstractJsonApiResource extends JsonApiResource
{
    public function toId(Request $request): string
    {
        $resource = $this->resource;

        if ($resource instanceof Model) {
            return (string) $resource->getRouteKey();
        }

        return parent::toId($request);
    }
}
