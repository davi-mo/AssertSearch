<?php

use App\Services\Elasticsearch\AssetSearchIndex;

it('reads elasticsearch index configuration', function () {
    config([
        'elasticsearch.index' => 'test-assets',
        'elasticsearch.vector_dimensions' => 768,
    ]);

    $index = app(AssetSearchIndex::class);

    expect($index->indexName())->toBe('test-assets')
        ->and($index->vectorDimensions())->toBe(768);
});
