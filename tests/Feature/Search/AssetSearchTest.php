<?php

use App\Services\Search\AssetSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires a search query', function () {
    $response = $this->getJson('/search');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['q']);
});

it('returns indexed search results with scores', function () {
    $search = Mockery::mock(AssetSearchService::class);
    $search->shouldReceive('search')
        ->once()
        ->with('headcount plans')
        ->andReturn([
            [
                'id' => 'ast_1001',
                'name' => 'Q3_deck_FINAL_v2',
                'description' => 'A quarterly business review deck with headcount plans for the next two quarters.',
                'score' => 0.9123,
            ],
        ]);

    app()->instance(AssetSearchService::class, $search);

    $this->getJson('/search?q=headcount plans')
        ->assertSuccessful()
        ->assertJson([
            'query' => 'headcount plans',
            'results' => [
                [
                    'id' => 'ast_1001',
                    'name' => 'Q3_deck_FINAL_v2',
                    'description' => 'A quarterly business review deck with headcount plans for the next two quarters.',
                    'score' => 0.9123,
                ],
            ],
        ]);
});

it('returns at most ten results', function () {
    $search = Mockery::mock(AssetSearchService::class);
    $search->shouldReceive('search')
        ->once()
        ->with('enterprise churn')
        ->andReturn(array_fill(0, 10, [
            'id' => 'ast_3001',
            'name' => 'churn_analysis_FY26',
            'description' => 'Enterprise churn analysis.',
            'score' => 0.85,
        ]));

    app()->instance(AssetSearchService::class, $search);

    $response = $this->getJson('/search?q=enterprise churn');

    expect($response->json('results'))->toHaveCount(10);
});

it('can surface semantic matches when the query words are absent from descriptions', function () {
    $search = Mockery::mock(AssetSearchService::class);
    $search->shouldReceive('search')
        ->once()
        ->with('hiring')
        ->andReturn([
            [
                'id' => 'ast_1001',
                'name' => 'Q3_deck_FINAL_v2',
                'description' => 'A quarterly business review deck: revenue against target by region, a churn breakdown for enterprise accounts, and a closing slide of headcount plans for the next two quarters.',
                'score' => 0.8871,
            ],
            [
                'id' => 'ast_2002',
                'name' => 'headcount_model_v4',
                'description' => 'Spreadsheet-backed headcount model projecting FTE growth by department, including backfills and new role approvals for the next two quarters.',
                'score' => 0.8542,
            ],
        ]);

    app()->instance(AssetSearchService::class, $search);

    $response = $this->getJson('/search?q=hiring');

    expect($response->json('results.0.description'))->not->toContain('hiring')
        ->and($response->json('results.0.description'))->toContain('headcount');
});
