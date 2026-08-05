<?php

it('returns service info at the root', function () {
    $this->getJson('/')
        ->assertSuccessful()
        ->assertJson([
            'service' => 'asset-search',
        ])
        ->assertJsonStructure(['service', 'search']);
});
