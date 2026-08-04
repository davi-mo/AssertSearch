<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Hosts
    |--------------------------------------------------------------------------
    |
    | A list of Elasticsearch node URLs. In Docker, use the service hostname.
    |
    */

    'hosts' => [
        env('ELASTICSEARCH_HOST', 'http://localhost:9200'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Search Index
    |--------------------------------------------------------------------------
    */

    'index' => env('ELASTICSEARCH_INDEX', 'assets'),

    'vector_dimensions' => (int) env('EMBEDDING_DIMENSIONS', 768),

];
