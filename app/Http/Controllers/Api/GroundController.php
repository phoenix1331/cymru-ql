<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginationRequest;
use App\Http\Resources\GroundResource;
use App\Services\GroundService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GroundController extends Controller
{
    public function __construct(private readonly GroundService $grounds) {}

    public function index(PaginationRequest $request): AnonymousResourceCollection
    {
        return GroundResource::collection(
            $this->grounds->paginate($request->integer('per_page', 20))->withQueryString(),
        );
    }

    public function show(string $ground): GroundResource
    {
        return new GroundResource($this->grounds->find($ground)->load('clubs'));
    }
}
