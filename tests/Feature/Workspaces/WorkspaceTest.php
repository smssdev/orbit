<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia;

test('registering creates a personal workspace', function (): void {
    $this->post(route('register.store'), [
        'name' => 'Lina Haddad',
        'email' => 'lina@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'lina@example.com')->firstOrFail();

    expect($user->currentWorkspace?->name)->toBe("Lina's workspace")
        ->and($user->currentWorkspace?->owner_id)->toBe($user->id)
        ->and($user->currentWorkspace?->roleOf($user))->toBe(WorkspaceRole::Owner);
});

test('the create workspace page can be rendered', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('workspaces.create'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->component('workspaces/Create'));
});

test('users can create a workspace and are switched into it', function (): void {
    $user = User::factory()->withWorkspace()->create();

    $this->actingAs($user)
        ->post(route('workspaces.store'), ['name' => 'Side project'])
        ->assertRedirect(route('dashboard'));

    $workspace = Workspace::query()->where('name', 'Side project')->firstOrFail();

    expect($workspace->owner_id)->toBe($user->id)
        ->and($workspace->roleOf($user))->toBe(WorkspaceRole::Owner)
        ->and($user->fresh()?->current_workspace_id)->toBe($workspace->id);
});

test('a workspace needs a valid name', function (string $name): void {
    $user = User::factory()->withWorkspace()->create();

    $this->actingAs($user)
        ->post(route('workspaces.store'), ['name' => $name])
        ->assertSessionHasErrors('name');
})->with([
    'empty' => '',
    'too long' => str_repeat('a', 81),
]);

test('the settings page tells members whether they can edit', function (WorkspaceRole $role, bool $canUpdate): void {
    $workspace = Workspace::factory()->create(['name' => 'Acme']);
    $user = User::factory()->create();
    $workspace->addMember($user, $role);
    $user->switchWorkspace($workspace);

    $this->actingAs($user)
        ->get(route('workspace.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('workspaces/Settings')
            ->where('workspace.name', 'Acme')
            ->where('canUpdate', $canUpdate),
        );
})->with([
    'admin' => [WorkspaceRole::Admin, true],
    'member' => [WorkspaceRole::Member, false],
]);

test('owners and admins can rename the workspace', function (WorkspaceRole $role): void {
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create();
    $workspace->addMember($user, $role);
    $user->switchWorkspace($workspace);

    $this->actingAs($user)
        ->patch(route('workspace.update'), ['name' => 'Renamed'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($workspace->fresh()?->name)->toBe('Renamed');
})->with([WorkspaceRole::Owner, WorkspaceRole::Admin]);

test('members cannot rename the workspace', function (): void {
    $workspace = Workspace::factory()->create(['name' => 'Acme']);
    $user = User::factory()->create();
    $workspace->addMember($user, WorkspaceRole::Member);
    $user->switchWorkspace($workspace);

    $this->actingAs($user)
        ->patch(route('workspace.update'), ['name' => 'Renamed'])
        ->assertForbidden();

    expect($workspace->fresh()?->name)->toBe('Acme');
});
