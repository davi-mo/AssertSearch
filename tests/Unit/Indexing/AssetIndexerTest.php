<?php

use App\Models\Asset;
use App\Services\Elasticsearch\AssetSearchIndex;
use App\Services\Embeddings\EmbeddingGenerator;
use App\Services\Indexing\AssetIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\EmbeddingsResponse;

uses(RefreshDatabase::class);

it('embeds the description and upserts into the search index', function () {
    Embeddings::fake([
        new EmbeddingsResponse(
            [Embeddings::fakeEmbedding(768)],
            1,
            new Meta('embeddings', 'nomic-embed-text'),
        ),
    ]);

    $index = Mockery::mock(AssetSearchIndex::class);
    $index->shouldReceive('upsert')
        ->once()
        ->with(
            'ast_8831',
            'Q3_deck_FINAL_v2',
            'Headcount plans for the next two quarters.',
            Mockery::type('array'),
        );

    $indexer = new AssetIndexer(app(EmbeddingGenerator::class), $index);

    $indexer->index(new Asset([
        'id' => 'ast_8831',
        'name' => 'Q3_deck_FINAL_v2',
        'description' => 'Headcount plans for the next two quarters.',
    ]));
});

it('indexes all assets in batches', function () {
    Embeddings::fake([
        new EmbeddingsResponse(
            [
                Embeddings::fakeEmbedding(768),
                Embeddings::fakeEmbedding(768),
            ],
            2,
            new Meta('embeddings', 'nomic-embed-text'),
        ),
    ]);

    $index = Mockery::mock(AssetSearchIndex::class);
    $index->shouldReceive('ensureIndex')->once();
    $index->shouldReceive('upsert')->twice();

    $indexer = new AssetIndexer(app(EmbeddingGenerator::class), $index);

    Asset::withoutEvents(function () use ($indexer): void {
        Asset::query()->create([
            'id' => 'ast_0001',
            'name' => 'first',
            'description' => 'First asset description.',
        ]);

        Asset::query()->create([
            'id' => 'ast_0002',
            'name' => 'second',
            'description' => 'Second asset description.',
        ]);

        expect($indexer->indexAll())->toBe(2);
    });
});

it('removes an asset from the search index', function () {
    $index = Mockery::mock(AssetSearchIndex::class);
    $index->shouldReceive('delete')->once()->with('ast_0001');

    $indexer = new AssetIndexer(app(EmbeddingGenerator::class), $index);

    $indexer->remove(new Asset(['id' => 'ast_0001']));
});
