<?php

use App\Models\Asset;
use App\Services\Indexing\AssetIndexer;
use Database\Seeders\AssetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $indexer = Mockery::mock(AssetIndexer::class);
    $indexer->shouldIgnoreMissing();

    app()->instance(AssetIndexer::class, $indexer);
});

it('seeds the committed sample asset library', function () {
    $this->seed(AssetSeeder::class);

    expect(Asset::query()->count())->toBe(110)
        ->and(Asset::query()->find('ast_1001')?->name)->toBe('Q3_deck_FINAL_v2')
        ->and(Asset::query()->find('ast_2002')?->description)->toContain('headcount');
});

it('includes deliberate edge cases in the sample data', function () {
    $this->seed(AssetSeeder::class);

    $misleading = Asset::query()->find('ast_9001');
    $duplicateDescription = Asset::query()->find('ast_9004');
    $thinDescription = Asset::query()->find('ast_9006');

    expect($misleading?->name)->toBe('vacation_policy_final')
        ->and($misleading?->description)->toContain('Security audit')
        ->and($duplicateDescription?->description)->toBe(Asset::query()->find('ast_1001')?->description)
        ->and($thinDescription?->description)->toBe('Draft.');
});

it('can re-run the seeder without creating duplicates', function () {
    $this->seed(AssetSeeder::class);
    $this->seed(AssetSeeder::class);

    expect(Asset::query()->count())->toBe(110);
});
