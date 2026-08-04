<?php

use App\Services\Embeddings\EmbeddingGenerator;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\EmbeddingsResponse;

it('generates embeddings using the configured dimensions', function () {
    Embeddings::fake([
        new EmbeddingsResponse(
            [Embeddings::fakeEmbedding(768)],
            1,
            new Meta('embeddings', 'nomic-embed-text'),
        ),
    ]);

    $embedding = app(EmbeddingGenerator::class)->embed('Quarterly hiring plans');

    expect($embedding)->toHaveCount(768);

    Embeddings::assertGenerated(fn ($prompt) => $prompt->contains('Quarterly hiring plans'));
});

it('generates embeddings for multiple texts', function () {
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

    $embeddings = app(EmbeddingGenerator::class)->embedMany([
        'Quarterly hiring plans',
        'Enterprise churn breakdown',
    ]);

    expect($embeddings)->toHaveCount(2)
        ->and($embeddings[0])->toHaveCount(768)
        ->and($embeddings[1])->toHaveCount(768);
});
