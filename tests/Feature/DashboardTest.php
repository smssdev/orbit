<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->withWorkspace()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard sums up the current workspace and its active projects', function (): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);
    $project = Project::factory()->for($workspace)->create(['name' => 'Launch']);
    [$todo, $doing] = $project->columns;
    Task::factory()->for($todo)->count(2)->create();
    Task::factory()->for($doing)->create();
    Project::factory()->for($workspace)->archived()->create();
    Project::factory()->create(); // another workspace's

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('Dashboard')
            ->where('stats.projects', 1)
            ->where('stats.tasks', 3)
            ->where('stats.members', $workspace->members()->count())
            ->has('projects', 1)
            ->where('projects.0.name', 'Launch')
            ->where('projects.0.tasks', 3)
            ->where('projects.0.columns.0.tasks', 2)
            ->where('projects.0.columns.1.tasks', 1));
});
