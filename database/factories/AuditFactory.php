<?php

namespace Database\Factories;

use App\Enums\WorkflowStatus;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AuditFactory extends Factory
{
    protected $model = Audit::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(WorkflowStatus::cases()),
            'audit_type' => $this->faker->randomElement(['standards', 'implementations', 'program']),
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays(30),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    /**
     * Indicate that the audit has a manager.
     */
    public function withManager(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'manager_id' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the audit is not started.
     */
    public function notStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::NOTSTARTED,
        ]);
    }

    /**
     * Indicate that the audit is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::INPROGRESS,
        ]);
    }

    /**
     * Indicate that the audit is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::COMPLETED,
        ]);
    }

    /**
     * Indicate that the audit has specific dates.
     */
    public function withDates(Carbon $startDate, Carbon $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
