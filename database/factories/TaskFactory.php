<?php

namespace Database\Factories;

use App\Models\Column;
use App\Models\Task;
use App\Support\Positions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'column_id' => Column::factory(),
            'project_id' => fn (array $attributes): int => Column::query()->findOrFail($this->id($attributes['column_id']))->project_id,
            'title' => fake()->sentence(4),
            // At the bottom of its column.
            'position' => fn (array $attributes): int => Positions::last(Task::query()->where('column_id', $attributes['column_id'])) + Positions::GAP,
        ];
    }

    private function id(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
