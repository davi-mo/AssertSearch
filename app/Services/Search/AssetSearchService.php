<?php

namespace App\Services\Search;

use App\Services\Elasticsearch\AssetSearchIndex;
use App\Services\Embeddings\EmbeddingGenerator;

class AssetSearchService
{
    public function __construct(
        private EmbeddingGenerator $embeddings,
        private AssetSearchIndex $index,
    ) {}

    /**
     * @return array<int, array{id: string, name: string, description: string, score: float}>
     */
    public function search(string $query, int $limit = 10): array
    {
        $queryVector = $this->embeddings->embed($query);

        $results = $this->index->search($queryVector, $limit);

        return array_map(
            fn (array $result): array => [
                'id' => $result['asset_id'],
                'name' => $result['name'],
                'description' => $result['description'],
                'score' => $result['score'],
            ],
            $results,
        );
    }
}
