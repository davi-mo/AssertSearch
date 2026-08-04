<?php

use App\Models\Asset;
use App\Services\Indexing\AssetIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $indexer = Mockery::mock(AssetIndexer::class);
    $indexer->shouldIgnoreMissing();

    app()->instance(AssetIndexer::class, $indexer);
});

it('persists an asset with the assignment shape', function () {
    $asset = Asset::withoutEvents(fn () => Asset::query()->create([
        'id' => 'ast_8831',
        'name' => 'Q3_deck_FINAL_v2',
        'description' => 'A quarterly business review deck with hiring plans for the next two quarters.',
    ]));

    $asset->refresh();

    expect($asset->id)->toBe('ast_8831')
        ->and($asset->name)->toBe('Q3_deck_FINAL_v2')
        ->and($asset->description)->toContain('hiring plans');
});

it('can be created from the factory', function () {
    $asset = Asset::withoutEvents(fn () => Asset::factory()->create());

    expect($asset->id)->toStartWith('ast_')
        ->and($asset->name)->not->toBeEmpty()
        ->and($asset->description)->not->toBeEmpty();
});

it('uses the string id as the primary key', function () {
    $asset = Asset::withoutEvents(fn () => Asset::factory()->create(['id' => 'ast_1001']));

    expect(Asset::query()->find('ast_1001')?->is($asset))->toBeTrue();
});
