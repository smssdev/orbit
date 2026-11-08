<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia;

test('users without a workspace are sent to create one', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('workspaces.create'));
});

test('users fall back to their oldest workspace when the current one is gone', function (): void {
    $user = User::factory()->create();
    $this->travel(-2)->days();
    $oldest = Workspace::factory()->withMember($user)->create();
    $this->travelBack();
    $newer = Workspace::factory()->withMember($user)->create();
    $user->switchWorkspace($newer);

    $newer->members()->detach($user->id);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->fresh()?->current_workspace_id)->toBe($oldest->id);
});

test('the current workspace and the workspace list are shared with every page', function (): void {
    $user = User::factory()->withWorkspace('Zeta')->create();
    $other = Workspace::factory()->withMember($user, WorkspaceRole::Admin)->create(['name' => 'Alpha']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('currentWorkspace.name', 'Zeta')
            ->where('currentWorkspace.role', 'owner')
            ->has('workspaces', 2)
            ->where('workspaces.0.id', $other->id)
            ->where('workspaces.0.role', 'admin')
            ->where('workspaces.1.name', 'Zeta'),
        );
});

test('users can switch to a workspace they belong to', function (): void {
    $user = User::factory()->withWorkspace()->create();
    $other = Workspace::factory()->withMember($user)->create();

    $this->actingAs($user)
        ->put(route('current-workspace.update'), ['workspace_id' => $other->id])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()?->current_workspace_id)->toBe($other->id);
});

test('users cannot switch to a workspace they do not belong to', function (): void {
    $user = User::factory()->withWorkspace()->create();
    $stranger = Workspace::factory()->create();

    $this->actingAs($user)
        ->put(route('current-workspace.update'), ['workspace_id' => $stranger->id])
        ->assertForbidden();

    expect($user->fresh()?->current_workspace_id)->not->toBe($stranger->id);
});
