<?php

namespace Database\Factories;

use App\Enums\ResponseStatus;
use App\Models\DataRequest;
use App\Models\DataRequestResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataRequestResponseFactory extends Factory
{
    protected $model = DataRequestResponse::class;

    public function definition(): array
    {
        return [
            'response' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(ResponseStatus::cases()),
            'data_request_id' => DataRequest::factory(),
            'requester_id' => User::factory(),
            'requestee_id' => User::factory(),
            'due_at' => $this->faker->optional(0.7)->dateTimeBetween('now', '+30 days'),
        ];
    }

    /**
     * Indicate that the response is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ResponseStatus::PENDING,
            'response' => null,
        ]);
    }

    /**
     * Indicate that the response has been responded to.
     */
    public function responded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ResponseStatus::RESPONDED,
        ]);
    }

    /**
     * Indicate that the response has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ResponseStatus::ACCEPTED,
        ]);
    }

    /**
     * Indicate that the response has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ResponseStatus::REJECTED,
        ]);
    }

    /**
     * Indicate that the response has a due date.
     */
    public function withDueDate(\DateTimeInterface $dueAt): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => $dueAt,
        ]);
    }

    /**
     * Indicate that the response is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ResponseStatus::PENDING,
            'due_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
