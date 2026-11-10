<?php

use App\Models\Column;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use App\Support\Positions;
use Inertia\Testing\AssertableInertia;

test('a new project starts with To do, In progress and Done', function (): void {
    $project = Project::factory()->create();

    expect($project->columns()->pluck('name')->all())->toBe(['To do', 'In progress', 'Done']);
});

test('the project page shows the board in order', function (): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);
    $project = Project::factory()->for($workspace)->create();
    [$todo, $doing] = $project->columns;
    $second = Task::factory()->for($todo)->create(['title' => 'Second', 'position' => 2048]);
    $first = Task::factory()->for($todo)->create(['title' => 'First', 'position' => 1024]);
    Task::factory()->for($doing)->create(['title' => 'Busy']);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('projects/Show')
            ->where('project.can.editBoard', true)
            ->has('columns', 3)
            ->where('columns.0.name', 'To do')
            ->has('columns.0.tasks', 2)
            ->where('columns.0.tasks.0.id', $first->id)
            ->where('columns.0.tasks.0.title', 'First')
            ->where('columns.0.tasks.1.id', $second->id)
            ->where('columns.0.tasks.1.title', 'Second')
            ->where('columns.1.tasks.0.title', 'Busy')
            ->where('columns.2.tasks', []));
});

test('an archived project’s board is read-only', function (): void {
    $workspace = Workspace::factory()->create();
    $user = projectMember($workspace);
    $project = Project::factory()->for($workspace)->archived()->create();

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('project.can.editBoard', false)
            ->has('columns', 3));
});

test('existing projects get the default columns, and projects with columns are left alone', function (): void {
    $bare = Project::factory()->create();
    $bare->columns()->delete();
    $custom = Project::factory()->create();
    $custom->columns()->delete();
    Column::factory()->for($custom)->create(['name' => 'Backlog']);

    $migration = require database_path('migrations/2026_11_09_100001_add_default_columns_to_existing_projects.php');
    $migration->up();
    $migration->up();

    expect($bare->columns()->pluck('name')->all())->toBe(['To do', 'In progress', 'Done'])
        ->and($bare->columns()->pluck('position')->all())->toBe([Positions::GAP, 2 * Positions::GAP, 3 * Positions::GAP])
        ->and($custom->columns()->pluck('name')->all())->toBe(['Backlog']);
});

test('positions take the midpoint and renumber when the gap runs out', function (): void {
    $column = Column::factory()->create();
    $a = Task::factory()->for($column)->create(['position' => 10]);
    $b = Task::factory()->for($column)->create(['position' => 11]);
    $c = Task::factory()->for($column)->create(['position' => 20]);
    $siblings = fn () => Task::query()->whereBelongsTo($column);

    expect(Positions::before($siblings(), $c->id))->toBe(15)
        ->and(Positions::before($siblings(), null))->toBe(20 + Positions::GAP)
        ->and(Positions::before(Task::query()->whereKey(0), null))->toBe(Positions::GAP);

    // No room between 10 and 11: the column is renumbered first, keeping its order.
    expect(Positions::before($siblings(), $b->id))->toBe(intdiv(Positions::GAP + 2 * Positions::GAP, 2))
        ->and($siblings()->orderBy('position')->pluck('id')->all())->toBe([$a->id, $b->id, $c->id])
        ->and($siblings()->orderBy('position')->pluck('position')->all())->toBe([Positions::GAP, 2 * Positions::GAP, 3 * Positions::GAP]);

    // Before the first one: between 0 and its position.
    expect(Positions::before($siblings(), $a->id))->toBe(intdiv(Positions::GAP, 2));
});
