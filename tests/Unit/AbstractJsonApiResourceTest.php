<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Streeboga\LaravelApi\Http\Resources\AbstractJsonApiResource;
use Streeboga\LaravelApi\Tests\Fixtures\Models\TestBook;

uses(RefreshDatabase::class);

final class TestBookResource extends AbstractJsonApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        /** @var TestBook $book */
        $book = $this->resource;

        return ['title' => $book->title];
    }

    public function toType(Request $request): string
    {
        return 'books';
    }
}

it('uses getRouteKey as id', function () {
    $book = TestBook::query()->create(['key' => 'bk_42', 'title' => 'Dune']);

    $resource = new TestBookResource($book);
    $request = Request::create('/', 'GET');

    expect($resource->toId($request))->toBe('bk_42');
});

it('produces JSON:API structure via toArray', function () {
    $book = TestBook::query()->create(['key' => 'bk_77', 'title' => 'Foundation']);

    $resource = new TestBookResource($book);
    $request = Request::create('/', 'GET');

    $array = $resource->toArray($request);

    expect($array)->toHaveKeys(['id', 'type', 'attributes'])
        ->and($array['id'])->toBe('bk_77')
        ->and($array['type'])->toBe('books')
        ->and((array) $array['attributes'])->toBe(['title' => 'Foundation']);
});
