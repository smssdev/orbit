<?php

use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia;

/**
 * A user in the workspace with the given role, switched into it.
 */
function projectMember(Workspace $workspace, WorkspaceRole $role = WorkspaceRole::Member): User
{
    $user = User::factory()->create();
    $workspace->addMember($user, $role);
    $user->switchWorkspace($workspace);

    return $user;
}

/**
 * @return array<string, string>
 */
function projectPayload(array $overrides = []): array
{
    return [...['name' => 'Website relaunch', 'description' => 'New site by spring', 'color' => 'blue', 'icon' => 'rocket'], ...$overrides];
}

test('members can create a project and land on it', function (): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);

    $response = $this->actingAs($user)->post(route('projects.store'), projectPayload());

    $project = Project::query()->firstOrFail();

    $response->assertRedirect(route('projects.show', $project));

    expect($project->workspace_id)->toBe($workspace->id)
        ->and($project->created_by)->toBe($user->id)
        ->and($project->name)->toBe('Website relaunch')
        ->and($project->color)->toBe(ProjectColor::Blue)
        ->and($project->icon)->toBe(ProjectIcon::Rocket)
        ->and($project->isArchived())->toBeFalse();
});

test('a blank description is stored as none', function (): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);

    $this->actingAs($user)->post(route('projects.store'), projectPayload(['description' => '   ']));

    expect(Project::query()->firstOrFail()->description)->toBeNull();
});

test('a project needs a valid name, colour and icon', function (string $field, string $value): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);

    $this->actingAs($user)
        ->post(route('projects.store'), projectPayload([$field => $value]))
        ->assertSessionHasErrors($field);

    expect(Project::query()->count())->toBe(0);
})->with([
    'empty name' => ['name', ''],
    'long name' => ['name', str_repeat('a', 81)],
    'long description' => ['description', str_repeat('a', 501)],
    'unknown colour' => ['color', 'chartreuse'],
    'unknown icon' => ['icon', 'dragon'],
]);

test('the list shows only the current workspace’s active projects', function (): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);
    Project::factory()->for($workspace)->create(['name' => 'Beta']);
    Project::factory()->for($workspace)->create(['name' => 'Alpha']);
    Project::factory()->for($workspace)->archived()->create(['name' => 'Old']);
    Project::factory()->create(['name' => 'Elsewhere']);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('projects/Index')
            ->where('filter', 'active')
            ->where('projects.0.name', 'Alpha')
            ->where('projects.1.name', 'Beta')
            ->count('projects', 2)
            ->where('counts', ['active' => 2, 'archived' => 1]),
        );
});

test('the archived filter lists the archived projects', function (): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);
    Project::factory()->for($workspace)->create();
    Project::factory()->for($workspace)->archived()->create(['name' => 'Old']);

    $this->actingAs($user)
        ->get(route('projects.index', ['filter' => 'archived']))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('filter', 'archived')
            ->count('projects', 1)
            ->where('projects.0.name', 'Old'),
        );
});

test('the sidebar gets the current workspace’s active projects', function (): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);
    Project::factory()->for($workspace)->create(['name' => 'Roadmap', 'color' => 'green', 'icon' => 'star']);
    Project::factory()->for($workspace)->archived()->create();
    Project::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->count('projects', 1)
            ->where('projects.0.name', 'Roadmap')
            ->where('projects.0.color', 'green')
            ->where('projects.0.icon', 'star'),
        );
});

test('a project page opens for members of its workspace', function (): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);
    $project = Project::factory()->for($workspace)->create(['name' => 'Launch']);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('projects/Show')
            ->where('project.name', 'Launch')
            ->where('project.can.update', false)
            ->where('project.can.delete', false),
        );
});

test('a project from another workspace answers not found', function (string $method, string $route): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace, WorkspaceRole::Admin);
    $other = Project::factory()->create();

    $this->actingAs($user)
        ->{$method}(route($route, $other), $method === 'patch' ? projectPayload() : [])
        ->assertNotFound();

    expect($other->fresh()?->name)->not->toBe('Website relaunch')
        ->and($other->fresh()?->isArchived())->toBeFalse();
})->with([
    'show' => ['get', 'projects.show'],
    'update' => ['patch', 'projects.update'],
    'archive' => ['post', 'projects.archive'],
    'delete' => ['delete', 'projects.destroy'],
]);

test('the creator, owners and admins can edit a project', function (string $who): void {
    $workspace = Workspace::factory()->create();
    $creator = projectMember($workspace);
    $project = Project::factory()->for($workspace)->create(['created_by' => $creator->id]);
    $user = match ($who) {
        'creator' => $creator,
        'admin' => projectMember($workspace, WorkspaceRole::Admin),
        'owner' => tap($workspace->owner)->switchWorkspace($workspace),
    };

    $this->actingAs($user)
        ->patch(route('projects.update', $project), projectPayload(['name' => 'Renamed', 'color' => 'pink', 'icon' => 'heart']))
        ->assertRedirect();

    expect($project->fresh())
        ->name->toBe('Renamed')
        ->color->toBe(ProjectColor::Pink)
        ->icon->toBe(ProjectIcon::Heart);
})->with(['creator', 'admin', 'owner']);

test('other members cannot edit or archive a project', function (): void {
    $workspace = Workspace::factory()->create();
    $project = Project::factory()->for($workspace)->create(['name' => 'Kept']);
    $user = projectMember($workspace);

    $this->actingAs($user)->patch(route('projects.update', $project), projectPayload())->assertForbidden();
    $this->actingAs($user)->post(route('projects.archive', $project))->assertForbidden();

    expect($project->fresh())
        ->name->toBe('Kept')
        ->isArchived()->toBeFalse();
});

test('a project can be archived and restored', function (): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);
    $project = Project::factory()->for($workspace)->create(['created_by' => $user->id]);

    $this->actingAs($user)->post(route('projects.archive', $project))->assertRedirect(route('projects.index'));
    expect($project->fresh()?->isArchived())->toBeTrue();

    $this->actingAs($user)->delete(route('projects.restore', $project))->assertRedirect(route('projects.show', $project));
    expect($project->fresh()?->isArchived())->toBeFalse();
});

test('owners and admins delete archived projects only', function (): void {
    $workspace = Workspace::factory()->create();
    $admin = projectMember($workspace, WorkspaceRole::Admin);
    $active = Project::factory()->for($workspace)->create();
    $archived = Project::factory()->for($workspace)->archived()->create();

    $this->actingAs($admin)->delete(route('projects.destroy', $active))->assertForbidden();
    $this->actingAs($admin)->delete(route('projects.destroy', $archived))->assertRedirect(route('projects.index', ['filter' => 'archived']));

    expect($active->fresh())->not->toBeNull()
        ->and($archived->fresh())->toBeNull();
});

test('members cannot delete even their own archived project', function (): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);
    $project = Project::factory()->for($workspace)->archived()->create(['created_by' => $user->id]);

    $this->actingAs($user)->delete(route('projects.destroy', $project))->assertForbidden();

    expect($project->fresh())->not->toBeNull();
});

test('guests are sent to log in', function (): void {
    $project = Project::factory()->create();

    $this->get(route('projects.index'))->assertRedirect(route('login'));
    $this->get(route('projects.show', $project))->assertRedirect(route('login'));
});
