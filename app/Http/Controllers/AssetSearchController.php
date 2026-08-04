<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchAssetsRequest;
use App\Services\Search\AssetSearchService;
use Illuminate\Http\JsonResponse;

class AssetSearchController extends Controller
{
    /**
     * Search indexed assets by natural language query.
     */
    public function __invoke(SearchAssetsRequest $request, AssetSearchService $search): JsonResponse
    {
        $query = $request->validated('q');

        return response()->json([
            'query' => $query,
            'results' => $search->search($query),
        ]);
    }
}
