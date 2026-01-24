<?php

namespace Database\Factories;

use App\Enums\PolicyExceptionStatus;
use App\Models\Policy;
use App\Models\PolicyException;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyException>
 */
class PolicyExceptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $requestedDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $effectiveDate = $this->faker->dateTimeBetween($requestedDate, '+1 month');
        $expirationDate = $this->faker->optional(0.7)->dateTimeBetween($effectiveDate, '+1 year');

        return [
            'policy_id' => Policy::factory(),
            'name' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph,
            'justification' => $this->faker->paragraph,
            'risk_assessment' => $this->faker->optional(0.8)->paragraph,
            'compensating_controls' => $this->faker->optional(0.6)->paragraph,
            'status' => $this->faker->randomElement(PolicyExceptionStatus::cases()),
            'requested_date' => $requestedDate,
            'effective_date' => $effectiveDate,
            'expiration_date' => $expirationDate,
        ];
    }

    /**
     * Indicate that the exception is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PolicyExceptionStatus::Pending,
            'approved_by' => null,
        ]);
    }

    /**
     * Indicate that the exception is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PolicyExceptionStatus::Approved,
            'approved_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the exception is denied.
     */
    public function denied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PolicyExceptionStatus::Denied,
        ]);
    }

    /**
     * Indicate that the exception is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PolicyExceptionStatus::Expired,
            'expiration_date' => $this->faker->dateTimeBetween('-6 months', '-1 day'),
        ]);
    }

    /**
     * Indicate that the exception is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PolicyExceptionStatus::Revoked,
        ]);
    }
}
