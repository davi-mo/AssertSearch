<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchAssetsRequest;
use App\Services\Elasticsearch\ElasticsearchConnection;
use App\Services\Search\AssetSearchService;
use Illuminate\Http\JsonResponse;

class AssetSearchController extends Controller
{
    /**
     * Search indexed assets by natural language query.
     */
    public function __invoke(
        SearchAssetsRequest $request,
        AssetSearchService $search,
        ElasticsearchConnection $elasticsearch,
    ): JsonResponse {
        if (! $elasticsearch->isAvailable()) {
            $host = config('elasticsearch.hosts')[0] ?? 'unknown';

            return response()->json([
                'message' => "Cannot reach Elasticsearch at [{$host}]. Start it with: docker compose up -d elasticsearch",
            ], 503);
        }

        $query = $request->validated('q');

        return response()->json([
            'query' => $query,
            'results' => $search->search($query),
        ]);
    }
}
