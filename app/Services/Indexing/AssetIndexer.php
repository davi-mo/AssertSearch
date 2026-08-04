<?php

namespace App\Services\Indexing;

use App\Models\Asset;
use App\Services\Elasticsearch\AssetSearchIndex;
use App\Services\Embeddings\EmbeddingGenerator;

class AssetIndexer
{
    public function __construct(
        private EmbeddingGenerator $embeddings,
        private AssetSearchIndex $index,
    ) {}

    public function index(Asset $asset): void
    {
        $embedding = $this->embeddings->embed($asset->description);

        $this->index->upsert(
            $asset->id,
            $asset->name,
            $asset->description,
            $embedding,
        );
    }

    public function remove(Asset $asset): void
    {
        $this->index->delete($asset->id);
    }

    public function indexAll(): int
    {
        $this->index->ensureIndex();

        $count = 0;

        Asset::query()
            ->orderBy('id')
            ->chunk(20, function ($assets) use (&$count): void {
                $embeddings = $this->embeddings->embedMany(
                    $assets->pluck('description')->values()->all(),
                );

                foreach ($assets->values() as $position => $asset) {
                    $this->index->upsert(
                        $asset->id,
                        $asset->name,
                        $asset->description,
                        $embeddings[$position],
                    );

                    $count++;
                }
            });

        return $count;
    }
}
