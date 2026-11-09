<?php

use App\Models\Column;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;

/**
 * A member of a fresh workspace with one project in it (To do, In progress, Done).
 *
 * @return array{User, Project}
 */
function boardOwner(array $project = []): array
{
    $workspace = Workspace::factory()->create();

    return [projectMember($workspace), Project::factory()->for($workspace)->create($project)];
}

/**
 * @return list<string>
 */
function columnNames(Project $project): array
{
    return $project->columns()->pluck('name')->all();
}

test('members add a column at the end of the board', function (): void {
    [$user, $project] = boardOwner();

    $this->actingAs($user)
        ->from(route('projects.show', $project))
        ->post(route('projects.columns.store', $project), ['name' => '  Review  '])
        ->assertRedirect(route('projects.show', $project))
        ->assertSessionHasNoErrors();

    expect(columnNames($project))->toBe(['To do', 'In progress', 'Done', 'Review']);
});

test('a column needs a short name', function (string $name): void {
    [$user, $project] = boardOwner();

    $this->actingAs($user)
        ->post(route('projects.columns.store', $project), ['name' => $name])
        ->assertSessionHasErrors('name');

    expect($project->columns()->count())->toBe(3);
})->with([
    'empty' => [''],
    'blank' => ['   '],
    'long' => [str_repeat('a', 41)],
]);

test('a board holds a limited number of columns', function (): void {
    [$user, $project] = boardOwner();
    Column::factory()->for($project)->count(Column::MAX_PER_PROJECT - 3)->create();

    $this->actingAs($user)
        ->post(route('projects.columns.store', $project), ['name' => 'One too many'])
        ->assertSessionHasErrors('name');

    expect($project->columns()->count())->toBe(Column::MAX_PER_PROJECT);
});

test('members rename a column', function (): void {
    [$user, $project] = boardOwner();
    $column = $project->columns()->firstOrFail();

    $this->actingAs($user)
        ->patch(route('projects.columns.update', [$project, $column]), ['name' => 'Backlog'])
        ->assertSessionHasNoErrors();

    expect(columnNames($project))->toBe(['Backlog', 'In progress', 'Done']);

    $this->actingAs($user)
        ->patch(route('projects.columns.update', [$project, $column]), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('members move a column before another or to the end', function (): void {
    [$user, $project] = boardOwner();
    [$todo, $doing, $done] = $project->columns;

    $this->actingAs($user)
        ->put(route('projects.columns.position', [$project, $done]), ['before' => $todo->id])
        ->assertSessionHasNoErrors();

    expect(columnNames($project))->toBe(['Done', 'To do', 'In progress']);

    $this->actingAs($user)
        ->put(route('projects.columns.position', [$project, $todo]), ['before' => null])
        ->assertSessionHasNoErrors();

    expect(columnNames($project))->toBe(['Done', 'In progress', 'To do']);

    $this->actingAs($user)
        ->put(route('projects.columns.position', [$project, $todo]), ['before' => $doing->id])
        ->assertSessionHasNoErrors();

    expect(columnNames($project))->toBe(['Done', 'To do', 'In progress']);
});

test('a column moves only next to another column of the same board', function (string $case): void {
    [$user, $project] = boardOwner();
    $column = $project->columns()->firstOrFail();
    $before = match ($case) {
        'itself' => $column->id,
        'another board' => Project::factory()->create()->columns()->firstOrFail()->id,
        'gone' => 999_999,
    };

    $this->actingAs($user)
        ->put(route('projects.columns.position', [$project, $column]), ['before' => $before])
        ->assertSessionHasErrors('before');

    expect(columnNames($project))->toBe(['To do', 'In progress', 'Done']);
})->with(['itself', 'another board', 'gone']);

test('members delete an empty column', function (): void {
    [$user, $project] = boardOwner();
    $doing = $project->columns[1];

    $this->actingAs($user)
        ->delete(route('projects.columns.destroy', [$project, $doing]))
        ->assertSessionHasNoErrors();

    expect(columnNames($project))->toBe(['To do', 'Done']);
});

test('a column with cards hands them to another column before it goes', function (): void {
    [$user, $project] = boardOwner();
    [$todo, $doing, $done] = $project->columns;
    $kept = Task::factory()->for($done)->create(['title' => 'Already done']);
    $first = Task::factory()->for($doing)->create(['title' => 'First']);
    $second = Task::factory()->for($doing)->create(['title' => 'Second']);

    $this->actingAs($user)
        ->delete(route('projects.columns.destroy', [$project, $doing]))
        ->assertSessionHasErrors('move_to');

    expect(columnNames($project))->toBe(['To do', 'In progress', 'Done']);

    $this->actingAs($user)
        ->delete(route('projects.columns.destroy', [$project, $doing]), ['move_to' => $done->id])
        ->assertSessionHasNoErrors();

    expect(columnNames($project))->toBe(['To do', 'Done'])
        ->and(Task::query()->whereBelongsTo($done)->orderBy('position')->pluck('id')->all())->toBe([$kept->id, $first->id, $second->id])
        ->and(Task::query()->whereBelongsTo($todo)->count())->toBe(0);
});

test('cards only go to another column of the same board', function (string $case): void {
    [$user, $project] = boardOwner();
    $doing = $project->columns[1];
    Task::factory()->for($doing)->create();
    $target = match ($case) {
        'itself' => $doing->id,
        'another board' => Project::factory()->create()->columns()->firstOrFail()->id,
    };

    $this->actingAs($user)
        ->delete(route('projects.columns.destroy', [$project, $doing]), ['move_to' => $target])
        ->assertSessionHasErrors('move_to');

    expect($project->columns()->count())->toBe(3)
        ->and(Task::query()->whereBelongsTo($doing)->count())->toBe(1);
})->with(['itself', 'another board']);

test('an archived project’s columns cannot change', function (string $method, string $route, array $data): void {
    [$user, $project] = boardOwner();
    $column = $project->columns()->firstOrFail();
    $project->update(['archived_at' => now()]);

    $this->actingAs($user)
        ->{$method}(route($route, [$project, $column]), $data)
        ->assertForbidden();

    expect(columnNames($project))->toBe(['To do', 'In progress', 'Done']);
})->with([
    'add' => ['post', 'projects.columns.store', ['name' => 'Review']],
    'rename' => ['patch', 'projects.columns.update', ['name' => 'Backlog']],
    'move' => ['put', 'projects.columns.position', ['before' => null]],
    'delete' => ['delete', 'projects.columns.destroy', []],
]);

test('columns of another workspace’s project answer not found', function (string $method, string $route, array $data): void {
    [$user] = boardOwner();
    $project = Project::factory()->create();
    $column = $project->columns()->firstOrFail();

    $this->actingAs($user)
        ->{$method}(route($route, [$project, $column]), $data)
        ->assertNotFound();

    expect(columnNames($project))->toBe(['To do', 'In progress', 'Done']);
})->with([
    'add' => ['post', 'projects.columns.store', ['name' => 'Review']],
    'rename' => ['patch', 'projects.columns.update', ['name' => 'Backlog']],
    'move' => ['put', 'projects.columns.position', ['before' => null]],
    'delete' => ['delete', 'projects.columns.destroy', []],
]);

test('a column is only found within its own project', function (string $method, string $route, array $data): void {
    [$user, $project] = boardOwner();
    $other = Project::factory()->for($project->workspace)->create();
    $column = $other->columns()->firstOrFail();

    $this->actingAs($user)
        ->{$method}(route($route, [$project, $column]), $data)
        ->assertNotFound();

    expect(columnNames($other))->toBe(['To do', 'In progress', 'Done']);
})->with([
    'rename' => ['patch', 'projects.columns.update', ['name' => 'Backlog']],
    'move' => ['put', 'projects.columns.position', ['before' => null]],
    'delete' => ['delete', 'projects.columns.destroy', []],
]);

test('people who left the workspace cannot change its boards', function (): void {
    [$user, $project] = boardOwner();
    $outsider = User::factory()->create();
    $outsider->forceFill(['current_workspace_id' => $project->workspace_id])->save();

    $this->actingAs($outsider)
        ->post(route('projects.columns.store', $project), ['name' => 'Review']);

    expect(columnNames($project))->toBe(['To do', 'In progress', 'Done']);
});

test('guests are sent to log in', function (): void {
    $project = Project::factory()->create();

    $this->post(route('projects.columns.store', $project), ['name' => 'Review'])
        ->assertRedirect(route('login'));
});
