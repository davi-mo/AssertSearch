<?php

use App\Models\Asset;
use App\Services\Search\AssetSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SearchTestHelpers;

uses(RefreshDatabase::class);

beforeEach(function () {
    SearchTestHelpers::bindInMemoryAssetSearchIndex();
});

it('finds indexed assets through the search service after indexing', function () {
    SearchTestHelpers::fakeEmbeddingsFromMap([
        'Recruiting pipeline and headcount plans.' => SearchTestHelpers::unitVector(0),
        'hiring' => SearchTestHelpers::unitVector(0),
    ]);

    Asset::query()->create([
        'id' => 'ast_9001',
        'name' => 'talent_pipeline',
        'description' => 'Recruiting pipeline and headcount plans.',
    ]);

    $results = app(AssetSearchService::class)->search('hiring');

    expect($results)->toHaveCount(1)
        ->and($results[0]['id'])->toBe('ast_9001')
        ->and($results[0]['description'])->toContain('Recruiting pipeline')
        ->and($results[0]['score'])->toBeGreaterThan(0);
});

it('returns no results when nothing in the index matches the query', function () {
    SearchTestHelpers::fakeEmbeddingsFromMap([
        'Quarterly revenue and margin report.' => SearchTestHelpers::unitVector(1),
        'hiring' => SearchTestHelpers::unitVector(0),
    ]);

    Asset::query()->create([
        'id' => 'ast_9002',
        'name' => 'finance_report',
        'description' => 'Quarterly revenue and margin report.',
    ]);

    expect(app(AssetSearchService::class)->search('hiring'))->toBeEmpty();
});

it('does not return deleted assets in search results', function () {
    SearchTestHelpers::fakeEmbeddingsFromMap([
        'Recruiting pipeline and headcount plans.' => SearchTestHelpers::unitVector(0),
        'hiring' => SearchTestHelpers::unitVector(0),
    ]);

    $asset = Asset::query()->create([
        'id' => 'ast_9003',
        'name' => 'talent_pipeline',
        'description' => 'Recruiting pipeline and headcount plans.',
    ]);

    expect(app(AssetSearchService::class)->search('hiring'))->toHaveCount(1);

    $asset->delete();

    expect(app(AssetSearchService::class)->search('hiring'))->toBeEmpty();
});

it('returns the updated description after an asset is reindexed', function () {
    SearchTestHelpers::fakeEmbeddingsFromMap([
        'Original churn summary.' => SearchTestHelpers::unitVector(0),
        'Updated headcount and hiring plan.' => SearchTestHelpers::unitVector(1),
        'hiring' => SearchTestHelpers::unitVector(1),
    ]);

    $asset = Asset::query()->create([
        'id' => 'ast_9004',
        'name' => 'workforce_plan',
        'description' => 'Original churn summary.',
    ]);

    expect(app(AssetSearchService::class)->search('hiring'))->toBeEmpty();

    $asset->update(['description' => 'Updated headcount and hiring plan.']);

    $results = app(AssetSearchService::class)->search('hiring');

    expect($results)->toHaveCount(1)
        ->and($results[0]['description'])->toBe('Updated headcount and hiring plan.');
});

it('returns search results over http after indexing an asset', function () {
    SearchTestHelpers::mockElasticsearchConnection(isAvailable: true);

    SearchTestHelpers::fakeEmbeddingsFromMap([
        'Recruiting pipeline and headcount plans.' => SearchTestHelpers::unitVector(0),
        'hiring' => SearchTestHelpers::unitVector(0),
    ]);

    Asset::query()->create([
        'id' => 'ast_9005',
        'name' => 'talent_pipeline',
        'description' => 'Recruiting pipeline and headcount plans.',
    ]);

    $this->getJson('/search?q=hiring')
        ->assertSuccessful()
        ->assertJsonPath('query', 'hiring')
        ->assertJsonPath('results.0.id', 'ast_9005')
        ->assertJsonPath('results.0.description', 'Recruiting pipeline and headcount plans.');
});
