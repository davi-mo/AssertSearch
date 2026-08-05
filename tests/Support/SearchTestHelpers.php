<?php

namespace Tests\Support;

use App\Services\Elasticsearch\AssetSearchIndex;
use App\Services\Elasticsearch\ElasticsearchConnection;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\EmbeddingsResponse;
use Mockery;

final class SearchTestHelpers
{
    /**
     * @return array<int, float>
     */
    public static function unitVector(int $activeIndex, int $dimensions = 768): array
    {
        $vector = array_fill(0, $dimensions, 0.0);
        $vector[$activeIndex] = 1.0;

        return $vector;
    }

    public static function bindInMemoryAssetSearchIndex(): InMemorySearchStore
    {
        $store = new InMemorySearchStore;

        $index = Mockery::mock(AssetSearchIndex::class);
        $index->shouldReceive('ensureIndex')->byDefault()->andReturnNull();
        $index->shouldReceive('upsert')->andReturnUsing(
            function (string $assetId, string $name, string $description, array $embedding) use ($store): void {
                $store->upsert($assetId, $name, $description, $embedding);
            }
        );
        $index->shouldReceive('delete')->andReturnUsing(
            function (string $assetId) use ($store): void {
                $store->delete($assetId);
            }
        );
        $index->shouldReceive('search')->andReturnUsing(
            fn (array $queryVector, int $limit = 10): array => $store->search($queryVector, $limit)
        );

        app()->instance(AssetSearchIndex::class, $index);

        return $store;
    }

    /**
     * @param  array<string, array<int, float>>  $vectorsByText
     */
    public static function fakeEmbeddingsFromMap(array $vectorsByText): void
    {
        Embeddings::fake(function (EmbeddingsPrompt $prompt) use ($vectorsByText): EmbeddingsResponse {
            $embeddings = array_map(
                function (string $input) use ($vectorsByText): array {
                    if (array_key_exists($input, $vectorsByText)) {
                        return $vectorsByText[$input];
                    }

                    return self::unitVector(767);
                },
                $prompt->inputs,
            );

            return new EmbeddingsResponse(
                $embeddings,
                count($embeddings),
                new Meta('embeddings', 'nomic-embed-text'),
            );
        });
    }

    public static function mockElasticsearchConnection(bool $isAvailable): void
    {
        $connection = Mockery::mock(ElasticsearchConnection::class);
        $connection->shouldReceive('isAvailable')->andReturn($isAvailable);

        app()->instance(ElasticsearchConnection::class, $connection);
    }
}
