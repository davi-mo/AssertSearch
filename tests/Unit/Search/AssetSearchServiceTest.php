<?php

use App\Services\Elasticsearch\AssetSearchIndex;
use App\Services\Embeddings\EmbeddingGenerator;
use App\Services\Search\AssetSearchService;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\EmbeddingsResponse;

it('embeds the query and searches the elasticsearch index', function () {
    Embeddings::fake([
        new EmbeddingsResponse(
            [Embeddings::fakeEmbedding(768)],
            1,
            new Meta('embeddings', 'nomic-embed-text'),
        ),
    ]);

    $index = Mockery::mock(AssetSearchIndex::class);
    $index->shouldReceive('search')
        ->once()
        ->with(Mockery::type('array'), 10)
        ->andReturn([
            [
                'asset_id' => 'ast_1001',
                'name' => 'Q3_deck_FINAL_v2',
                'description' => 'Headcount plans for the next two quarters.',
                'score' => 0.91,
            ],
        ]);

    $results = (new AssetSearchService(app(EmbeddingGenerator::class), $index))->search('hiring');

    expect($results)->toBe([
        [
            'id' => 'ast_1001',
            'name' => 'Q3_deck_FINAL_v2',
            'description' => 'Headcount plans for the next two quarters.',
            'score' => 0.91,
        ],
    ]);
});
