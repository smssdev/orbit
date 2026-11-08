<?php

namespace Database\Factories;

use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->randomElement(ProjectColor::cases()),
            'icon' => fake()->randomElement(ProjectIcon::cases()),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['archived_at' => now()]);
    }
}
