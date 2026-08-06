<?php

namespace App\Services\Elasticsearch;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;

class AssetSearchIndex
{
    private bool $indexEnsured = false;

    public function __construct(private Client $client) {}

    public function indexName(): string
    {
        return config('elasticsearch.index');
    }

    public function vectorDimensions(): int
    {
        return config('elasticsearch.vector_dimensions');
    }

    public function ensureIndex(): void
    {
        if ($this->indexEnsured) {
            return;
        }

        if ($this->indexExists()) {
            $this->indexEnsured = true;

            return;
        }

        $this->client->indices()->create([
            'index' => $this->indexName(),
            'body' => [
                'mappings' => [
                    'properties' => [
                        'asset_id' => ['type' => 'keyword'],
                        'name' => ['type' => 'text'],
                        'description' => ['type' => 'text'],
                        'embedding' => [
                            'type' => 'dense_vector',
                            'dims' => $this->vectorDimensions(),
                            'index' => true,
                            'similarity' => 'cosine',
                        ],
                    ],
                ],
            ],
        ]);

        $this->indexEnsured = true;
    }

    /**
     * @param  array<int, float>  $embedding
     */
    public function upsert(string $assetId, string $name, string $description, array $embedding): void
    {
        $this->ensureIndex();

        $this->client->index([
            'index' => $this->indexName(),
            'id' => $assetId,
            'body' => [
                'asset_id' => $assetId,
                'name' => $name,
                'description' => $description,
                'embedding' => $embedding,
            ],
        ]);
    }

    public function delete(string $assetId): void
    {
        if (! $this->indexExists()) {
            return;
        }

        try {
            $this->client->delete([
                'index' => $this->indexName(),
                'id' => $assetId,
            ]);
        } catch (ClientResponseException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }
        }
    }

    /**
     * @param  array<int, float>  $queryVector
     * @return array<int, array{asset_id: string, name: string, description: string, score: float}>
     */
    public function search(array $queryVector, int $limit = 10): array
    {
        if (! $this->indexExists()) {
            return [];
        }

        /** @var Elasticsearch $response */
        $response = $this->client->search([
            'index' => $this->indexName(),
            'body' => [
                'size' => $limit,
                'knn' => [
                    'field' => 'embedding',
                    'query_vector' => $queryVector,
                    'k' => $limit,
                    'num_candidates' => max($limit * 10, 100),
                ],
            ],
        ]);

        $hits = $response->asArray()['hits']['hits'] ?? [];

        return array_map(
            fn (array $hit): array => [
                'asset_id' => $hit['_source']['asset_id'],
                'name' => $hit['_source']['name'],
                'description' => $hit['_source']['description'],
                'score' => (float) $hit['_score'],
            ],
            $hits,
        );
    }

    private function indexExists(): bool
    {
        return $this->client->indices()->exists(['index' => $this->indexName()])->asBool();
    }
}
