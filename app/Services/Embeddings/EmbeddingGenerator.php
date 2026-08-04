<?php

namespace App\Services\Embeddings;

use Laravel\Ai\Embeddings;
use Laravel\Ai\Responses\EmbeddingsResponse;

class EmbeddingGenerator
{
    /**
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        return $this->embedMany([$text])[0];
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedMany(array $texts): array
    {
        $response = $this->generate($texts);

        return $response->embeddings;
    }

    /**
     * @param  array<int, string>  $texts
     */
    public function generate(array $texts): EmbeddingsResponse
    {
        return Embeddings::for($texts)
            ->dimensions(config('elasticsearch.vector_dimensions'))
            ->generate();
    }
}
