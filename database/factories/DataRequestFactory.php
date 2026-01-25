<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\DataRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataRequestFactory extends Factory
{
    protected $model = DataRequest::class;

    public function definition(): array
    {
        return [
            'code' => 'DR-'.$this->faker->unique()->numerify('###'),
            'details' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['Pending', 'Responded', 'Accepted', 'Rejected']),
            'audit_id' => Audit::factory(),
            'created_by_id' => User::factory(),
            'assigned_to_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the data request is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Pending',
        ]);
    }

    /**
     * Indicate that the data request has been responded to.
     */
    public function responded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Responded',
        ]);
    }

    /**
     * Indicate that the data request has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Accepted',
        ]);
    }

    /**
     * Indicate that the data request has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Rejected',
        ]);
    }

    /**
     * Indicate that the data request is for a specific audit item.
     */
    public function forAuditItem(AuditItem $auditItem): static
    {
        return $this->state(fn (array $attributes) => [
            'audit_item_id' => $auditItem->id,
            'audit_id' => $auditItem->audit_id,
        ]);
    }
}
