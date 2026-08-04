<?php

namespace App\Observers;

use App\Models\Asset;
use App\Services\Indexing\AssetIndexer;

class AssetObserver
{
    public function __construct(private AssetIndexer $indexer) {}

    public function saved(Asset $asset): void
    {
        $this->indexer->index($asset);
    }

    public function deleted(Asset $asset): void
    {
        $this->indexer->remove($asset);
    }
}
