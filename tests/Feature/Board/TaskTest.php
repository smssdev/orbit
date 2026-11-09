<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;

/**
 * The card IDs of a column, top to bottom.
 *
 * @return list<int>
 */
function cardIds(Project $project, int $column): array
{
    return Task::query()->where('project_id', $project->id)->where('column_id', $column)->orderBy('position')->orderBy('id')->pluck('id')->all();
}

test('members add a card at the bottom of a column', function (): void {
    [$user, $project] = boardOwner();
    $todo = $project->columns()->firstOrFail();
    $existing = Task::factory()->for($todo)->create();

    $this->actingAs($user)
        ->from(route('projects.show', $project))
        ->post(route('projects.tasks.store', [$project, $todo]), ['title' => '  Write the brief  '])
        ->assertRedirect(route('projects.show', $project))
        ->assertSessionHasNoErrors();

    $task = Task::query()->latest('id')->firstOrFail();

    expect($task->title)->toBe('Write the brief')
        ->and($task->project_id)->toBe($project->id)
        ->and($task->created_by)->toBe($user->id)
        ->and(cardIds($project, $todo->id))->toBe([$existing->id, $task->id]);
});

test('a card needs a title', function (string $title): void {
    [$user, $project] = boardOwner();
    $todo = $project->columns()->firstOrFail();

    $this->actingAs($user)
        ->post(route('projects.tasks.store', [$project, $todo]), ['title' => $title])
        ->assertSessionHasErrors('title');

    expect(Task::query()->count())->toBe(0);
})->with([
    'empty' => [''],
    'blank' => ['   '],
    'long' => [str_repeat('a', 201)],
]);

test('members move a card within its column', function (): void {
    [$user, $project] = boardOwner();
    $todo = $project->columns()->firstOrFail();
    [$a, $b, $c] = Task::factory()->for($todo)->count(3)->create();

    $this->actingAs($user)
        ->put(route('projects.tasks.position', [$project, $c]), ['column' => $todo->id, 'before' => $a->id])
        ->assertSessionHasNoErrors();

    expect(cardIds($project, $todo->id))->toBe([$c->id, $a->id, $b->id]);

    $this->actingAs($user)
        ->put(route('projects.tasks.position', [$project, $c]), ['column' => $todo->id, 'before' => null])
        ->assertSessionHasNoErrors();

    expect(cardIds($project, $todo->id))->toBe([$a->id, $b->id, $c->id]);
});

test('members move a card to another column', function (): void {
    [$user, $project] = boardOwner();
    [$todo, $doing] = $project->columns;
    $moving = Task::factory()->for($todo)->create();
    $there = Task::factory()->for($doing)->create();

    $this->actingAs($user)
        ->put(route('projects.tasks.position', [$project, $moving]), ['column' => $doing->id, 'before' => $there->id])
        ->assertSessionHasNoErrors();

    expect(cardIds($project, $todo->id))->toBe([])
        ->and(cardIds($project, $doing->id))->toBe([$moving->id, $there->id]);

    $this->actingAs($user)
        ->put(route('projects.tasks.position', [$project, $there]), ['column' => $todo->id])
        ->assertSessionHasNoErrors();

    expect(cardIds($project, $todo->id))->toBe([$there->id]);
});

test('moves keep working when cards run out of room between them', function (): void {
    [$user, $project] = boardOwner();
    $todo = $project->columns()->firstOrFail();
    $first = Task::factory()->for($todo)->create();
    $last = Task::factory()->for($todo)->create();
    $moving = Task::factory()->for($todo)->create();
    $other = Task::factory()->for($todo)->create();

    // Each move lands between the same two cards, halving the gap until the column is renumbered.
    foreach (range(1, 12) as $i) {
        [$moving, $other] = [$other, $moving];

        $this->actingAs($user)
            ->put(route('projects.tasks.position', [$project, $moving]), ['column' => $todo->id, 'before' => $last->id])
            ->assertSessionHasNoErrors();
    }

    expect(cardIds($project, $todo->id))->toBe([$first->id, $other->id, $moving->id, $last->id])
        ->and(Task::query()->distinct()->count('position'))->toBe(4);
});

test('a card moves only within the same board, next to a card of the target column', function (string $case): void {
    [$user, $project] = boardOwner();
    [$todo, $doing] = $project->columns;
    $task = Task::factory()->for($todo)->create();
    $neighbour = Task::factory()->for($todo)->create();
    [$column, $before, $field] = match ($case) {
        'column of another board' => [Project::factory()->create()->columns()->firstOrFail()->id, null, 'column'],
        'no column' => [null, null, 'column'],
        'neighbour in another column' => [$doing->id, $neighbour->id, 'before'],
        'itself' => [$todo->id, $task->id, 'before'],
        'neighbour gone' => [$todo->id, 999_999, 'before'],
    };

    $this->actingAs($user)
        ->put(route('projects.tasks.position', [$project, $task]), ['column' => $column, 'before' => $before])
        ->assertSessionHasErrors($field);

    expect(cardIds($project, $todo->id))->toBe([$task->id, $neighbour->id]);
})->with(['column of another board', 'no column', 'neighbour in another column', 'itself', 'neighbour gone']);

test('an archived project’s cards cannot change', function (): void {
    [$user, $project] = boardOwner();
    [$todo, $doing] = $project->columns;
    $task = Task::factory()->for($todo)->create();
    $project->update(['archived_at' => now()]);

    $this->actingAs($user)
        ->post(route('projects.tasks.store', [$project, $todo]), ['title' => 'Late'])
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('projects.tasks.position', [$project, $task]), ['column' => $doing->id])
        ->assertForbidden();

    expect(cardIds($project, $todo->id))->toBe([$task->id]);
});

test('cards of another workspace’s project answer not found', function (): void {
    [$user] = boardOwner();
    $project = Project::factory()->create();
    [$todo, $doing] = $project->columns;
    $task = Task::factory()->for($todo)->create();

    $this->actingAs($user)
        ->post(route('projects.tasks.store', [$project, $todo]), ['title' => 'Sneaky'])
        ->assertNotFound();

    $this->actingAs($user)
        ->put(route('projects.tasks.position', [$project, $task]), ['column' => $doing->id])
        ->assertNotFound();

    expect(cardIds($project, $todo->id))->toBe([$task->id]);
});

test('columns and cards are only found within their own project', function (): void {
    [$user, $project] = boardOwner();
    $other = Project::factory()->for($project->workspace)->create();
    $foreignColumn = $other->columns()->firstOrFail();
    $foreignTask = Task::factory()->for($foreignColumn)->create();

    $this->actingAs($user)
        ->post(route('projects.tasks.store', [$project, $foreignColumn]), ['title' => 'Misplaced'])
        ->assertNotFound();

    $this->actingAs($user)
        ->put(route('projects.tasks.position', [$project, $foreignTask]), ['column' => $project->columns()->firstOrFail()->id])
        ->assertNotFound();

    expect(Task::query()->count())->toBe(1)
        ->and($foreignTask->refresh()->column_id)->toBe($foreignColumn->id);
});

test('guests cannot touch cards', function (): void {
    $project = Project::factory()->create();
    $todo = $project->columns()->firstOrFail();

    $this->post(route('projects.tasks.store', [$project, $todo]), ['title' => 'Anon'])
        ->assertRedirect(route('login'));

    expect(Task::query()->count())->toBe(0);
});

test('a project from the workspace of someone else is out of reach even for its column IDs', function (): void {
    [$user, $project] = boardOwner();
    $mine = $project->columns()->firstOrFail();
    $task = Task::factory()->for($mine)->create();
    $elsewhere = Workspace::factory()->create();
    $foreign = Project::factory()->for($elsewhere)->create()->columns()->firstOrFail();

    $this->actingAs($user)
        ->put(route('projects.tasks.position', [$project, $task]), ['column' => $foreign->id])
        ->assertSessionHasErrors('column');

    expect($task->refresh()->column_id)->toBe($mine->id);
});
