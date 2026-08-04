<?php

namespace App\Services\Elasticsearch;

use Elastic\Elasticsearch\Client;
use Elastic\Transport\Exception\NoNodeAvailableException;

class ElasticsearchConnection
{
    public function __construct(private Client $client) {}

    public function isAvailable(): bool
    {
        try {
            return $this->client->ping()->asBool();
        } catch (NoNodeAvailableException) {
            return false;
        }
    }
}
