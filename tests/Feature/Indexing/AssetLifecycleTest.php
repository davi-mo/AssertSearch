<?php

use App\Models\Asset;
use App\Services\Indexing\AssetIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('indexes assets when they are created', function () {
    $indexer = Mockery::mock(AssetIndexer::class);
    $indexer->shouldReceive('index')->once()->with(Mockery::on(
        fn (Asset $asset): bool => $asset->id === 'ast_7001'
    ));

    app()->instance(AssetIndexer::class, $indexer);

    Asset::query()->create([
        'id' => 'ast_7001',
        'name' => 'new_asset',
        'description' => 'A newly created asset description.',
    ]);
});

it('reindexes assets when their description is updated', function () {
    $indexer = Mockery::mock(AssetIndexer::class);
    $indexer->shouldReceive('index')->twice();

    app()->instance(AssetIndexer::class, $indexer);

    $asset = Asset::query()->create([
        'id' => 'ast_7002',
        'name' => 'updated_asset',
        'description' => 'Original description.',
    ]);

    $asset->update(['description' => 'Updated description with new headcount plans.']);
});

it('removes assets from the index when they are deleted', function () {
    $indexer = Mockery::mock(AssetIndexer::class);
    $indexer->shouldReceive('index')->once();
    $indexer->shouldReceive('remove')->once()->with(Mockery::on(
        fn (Asset $asset): bool => $asset->id === 'ast_7003'
    ));

    app()->instance(AssetIndexer::class, $indexer);

    $asset = Asset::query()->create([
        'id' => 'ast_7003',
        'name' => 'deleted_asset',
        'description' => 'This asset will be deleted.',
    ]);

    $asset->delete();
});
