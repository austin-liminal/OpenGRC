<?php

namespace Database\Factories;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use App\Enums\WorkflowStatus;
use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Control;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditItem>
 */
class AuditItemFactory extends Factory
{
    protected $model = AuditItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_id' => Audit::factory(),
            'auditable_id' => Control::factory(),
            'auditable_type' => Control::class,
            'status' => $this->faker->randomElement(WorkflowStatus::cases()),
            'effectiveness' => $this->faker->randomElement(Effectiveness::cases()),
            'applicability' => $this->faker->randomElement(Applicability::cases()),
            'auditor_notes' => $this->faker->optional(0.7)->paragraph(),
        ];
    }

    /**
     * Associate with a specific control.
     */
    public function forControl(Control $control): static
    {
        return $this->state(fn (array $attributes) => [
            'auditable_id' => $control->id,
            'auditable_type' => Control::class,
        ]);
    }

    /**
     * Indicate that the audit item has an assigned user.
     */
    public function withUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the audit item is not started.
     */
    public function notStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::NOTSTARTED,
            'effectiveness' => Effectiveness::UNKNOWN,
        ]);
    }

    /**
     * Indicate that the audit item is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::INPROGRESS,
        ]);
    }

    /**
     * Indicate that the audit item is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::COMPLETED,
        ]);
    }

    /**
     * Indicate that the audit item is effective.
     */
    public function effective(): static
    {
        return $this->state(fn (array $attributes) => [
            'effectiveness' => Effectiveness::EFFECTIVE,
            'status' => WorkflowStatus::COMPLETED,
        ]);
    }

    /**
     * Indicate that the audit item is partially effective.
     */
    public function partiallyEffective(): static
    {
        return $this->state(fn (array $attributes) => [
            'effectiveness' => Effectiveness::PARTIAL,
            'status' => WorkflowStatus::COMPLETED,
        ]);
    }

    /**
     * Indicate that the audit item is ineffective.
     */
    public function ineffective(): static
    {
        return $this->state(fn (array $attributes) => [
            'effectiveness' => Effectiveness::INEFFECTIVE,
            'status' => WorkflowStatus::COMPLETED,
        ]);
    }

    /**
     * Indicate that the control is applicable.
     */
    public function applicable(): static
    {
        return $this->state(fn (array $attributes) => [
            'applicability' => Applicability::APPLICABLE,
        ]);
    }

    /**
     * Indicate that the control is not applicable.
     */
    public function notApplicable(): static
    {
        return $this->state(fn (array $attributes) => [
            'applicability' => Applicability::NOTAPPLICABLE,
        ]);
    }
}
