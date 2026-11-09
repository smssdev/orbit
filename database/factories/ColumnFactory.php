<?php

namespace Database\Factories;

use App\Models\Column;
use App\Models\Project;
use App\Support\Positions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Column>
 */
class ColumnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(2, true),
            // After the project's existing columns.
            'position' => fn (array $attributes): int => Positions::last(Column::query()->where('project_id', $attributes['project_id'])) + Positions::GAP,
        ];
    }
}
