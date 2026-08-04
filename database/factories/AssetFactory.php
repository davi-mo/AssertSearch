<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 'ast_'.fake()->unique()->numerify('####'),
            'name' => fake()->randomElement([
                'Q3_deck_FINAL_v2',
                'brand_guidelines_2026',
                'onboarding_video_raw',
                'logo_pack_dark_mode',
                'customer_case_study_acme',
            ]),
            'description' => fake()->paragraph(),
        ];
    }
}
