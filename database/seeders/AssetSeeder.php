<?php

namespace Database\Seeders;

use App\Models\Asset;
use Illuminate\Database\Seeder;
use RuntimeException;

class AssetSeeder extends Seeder
{
    /**
     * Seed the application's asset library from committed sample data.
     */
    public function run(): void
    {
        $assets = $this->sampleAssets();

        foreach ($assets as $asset) {
            Asset::query()->updateOrCreate(
                ['id' => $asset['id']],
                [
                    'name' => $asset['name'],
                    'description' => $asset['description'],
                ],
            );
        }
    }

    /**
     * @return array<int, array{id: string, name: string, description: string}>
     */
    private function sampleAssets(): array
    {
        $path = database_path('data/assets.json');

        if (! is_file($path)) {
            throw new RuntimeException("Sample asset data file not found at [{$path}].");
        }

        $assets = json_decode(file_get_contents($path), true);

        if (! is_array($assets)) {
            throw new RuntimeException('Sample asset data file contains invalid JSON.');
        }

        return $assets;
    }
}
