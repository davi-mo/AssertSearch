<?php

use App\Models\Asset;
use App\Services\Elasticsearch\ElasticsearchConnection;
use App\Services\Indexing\AssetIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    mockElasticsearchAvailability(isAvailable: true);
});

it('loads sample assets and indexes them with one command', function () {
    $indexer = Mockery::mock(AssetIndexer::class);
    $indexer->shouldReceive('indexAll')->once()->andReturn(110);

    app()->instance(AssetIndexer::class, $indexer);

    $this->artisan('assets:index --seed')
        ->assertSuccessful()
        ->expectsOutputToContain('Indexed 110 assets');

    expect(Asset::query()->count())->toBe(110);
});

it('fails when no assets exist and seed was not requested', function () {
    $this->artisan('assets:index')
        ->assertFailed()
        ->expectsOutputToContain('No assets found');
});

it('fails with a helpful message when elasticsearch is unavailable', function () {
    mockElasticsearchAvailability(isAvailable: false);

    $this->artisan('assets:index --seed')
        ->assertFailed()
        ->expectsOutputToContain('Cannot reach Elasticsearch');
});

it('does not trigger lifecycle indexing while seeding via the command', function () {
    $indexer = Mockery::mock(AssetIndexer::class);
    $indexer->shouldReceive('index')->never();
    $indexer->shouldReceive('indexAll')->once()->andReturn(1);

    app()->instance(AssetIndexer::class, $indexer);

    Asset::withoutEvents(fn () => Asset::query()->create([
        'id' => 'ast_8001',
        'name' => 'existing',
        'description' => 'Already in the database.',
    ]));

    $this->artisan('assets:index')->assertSuccessful();
});

function mockElasticsearchAvailability(bool $isAvailable): void
{
    $connection = Mockery::mock(ElasticsearchConnection::class);
    $connection->shouldReceive('isAvailable')->andReturn($isAvailable);

    app()->instance(ElasticsearchConnection::class, $connection);
}
