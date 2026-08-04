<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Services\Elasticsearch\ElasticsearchConnection;
use App\Services\Indexing\AssetIndexer;
use Database\Seeders\AssetSeeder;
use Illuminate\Console\Command;

class IndexAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:index {--seed : Load the committed sample assets before indexing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Embed asset descriptions and build the searchable Elasticsearch index';

    /**
     * Execute the console command.
     */
    public function handle(AssetIndexer $indexer, ElasticsearchConnection $elasticsearch): int
    {
        if (! $elasticsearch->isAvailable()) {
            $host = config('elasticsearch.hosts')[0] ?? 'unknown';

            $this->components->error("Cannot reach Elasticsearch at [{$host}].");
            $this->line('Start Elasticsearch with: docker compose up -d elasticsearch');

            return self::FAILURE;
        }

        if ($this->option('seed')) {
            $this->components->info('Loading sample assets...');

            Asset::withoutEvents(function (): void {
                $this->call(AssetSeeder::class);
            });
        }

        if (Asset::query()->count() === 0) {
            $this->components->error('No assets found. Run with --seed or seed the database first.');

            return self::FAILURE;
        }

        $this->components->info('Indexing assets...');

        $count = $indexer->indexAll();

        $this->components->success("Indexed {$count} assets.");

        return self::SUCCESS;
    }
}
