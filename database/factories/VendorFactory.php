<?php

namespace Database\Factories;

use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->sentence(10),
            'url' => fake()->url(),
            'vendor_manager_id' => User::factory(),
            'status' => fake()->randomElement(VendorStatus::cases())->value,
            'risk_rating' => fake()->randomElement(VendorRiskRating::cases())->value,
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
