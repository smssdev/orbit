<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WorkspaceInvitation>
 */
class WorkspaceInvitationFactory extends Factory
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
            'email' => fake()->unique()->safeEmail(),
            'role' => WorkspaceRole::Member,
            'token_hash' => WorkspaceInvitation::hashToken(Str::random(64)),
            'expires_at' => now()->addDays(WorkspaceInvitation::EXPIRES_AFTER_DAYS),
        ];
    }

    /**
     * Use a known plain-text token, so tests can build the accept URL.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes): array => [
            'token_hash' => WorkspaceInvitation::hashToken($token),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
