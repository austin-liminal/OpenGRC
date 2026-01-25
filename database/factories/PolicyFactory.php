<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Policy>
 */
class PolicyFactory extends Factory
{
    protected $model = Policy::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'POL-'.$this->faker->unique()->numerify('###'),
            'name' => $this->faker->sentence(4),
            'document_type' => $this->faker->randomElement(DocumentType::cases()),
            'policy_scope' => $this->faker->optional(0.7)->paragraph,
            'purpose' => $this->faker->optional(0.8)->paragraph,
            'body' => $this->faker->optional(0.6)->paragraphs(3, true),
            'effective_date' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', '+1 month'),
            'retired_date' => null,
            'revision_history' => null,
        ];
    }

    /**
     * Indicate that the policy is a policy document type.
     */
    public function policy(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::Policy,
        ]);
    }

    /**
     * Indicate that the policy is a procedure document type.
     */
    public function procedure(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::Procedure,
        ]);
    }

    /**
     * Indicate that the policy is a standard document type.
     */
    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::Standard,
        ]);
    }

    /**
     * Indicate that the policy has an owner.
     */
    public function withOwner(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the policy is retired.
     */
    public function retired(): static
    {
        return $this->state(fn (array $attributes) => [
            'retired_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the policy has revision history.
     */
    public function withRevisionHistory(): static
    {
        return $this->state(fn (array $attributes) => [
            'revision_history' => [
                [
                    'version' => '1.0',
                    'date' => $this->faker->date(),
                    'author' => $this->faker->name(),
                    'changes' => 'Initial release',
                ],
                [
                    'version' => '1.1',
                    'date' => $this->faker->date(),
                    'author' => $this->faker->name(),
                    'changes' => $this->faker->sentence(),
                ],
            ],
        ]);
    }
}
