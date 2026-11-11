<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeasonResource;
use App\Services\SeasonService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class SeasonController extends Controller
{
    public function __invoke(SeasonService $seasons): AnonymousResourceCollection
    {
        return SeasonResource::collection($seasons->all());
    }
}
