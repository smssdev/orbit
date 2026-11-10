<?php

use App\Actions\Workspaces\RemoveMember;
use App\Enums\TaskPriority;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('members save a card’s details', function (): void {
    [$user, $project] = boardOwner();
    $teammate = projectMember($project->workspace);
    $task = Task::factory()->for($project->columns()->firstOrFail())->create();
    [$bug, $design] = Label::factory()->for($project)->count(2)->create();

    $this->actingAs($user)
        ->from(route('projects.show', $project))
        ->patch(route('projects.tasks.update', [$project, $task]), [
            'title' => '  Ship the panel  ',
            'description' => "  Two lines\nof notes  ",
            'assignee_id' => $teammate->id,
            'due_on' => '2026-12-01',
            'priority' => 'high',
            'labels' => [$bug->id, $design->id],
        ])
        ->assertRedirect(route('projects.show', $project))
        ->assertSessionHasNoErrors();

    $task->refresh();

    expect($task->title)->toBe('Ship the panel')
        ->and($task->description)->toBe("Two lines\nof notes")
        ->and($task->assignee_id)->toBe($teammate->id)
        ->and($task->due_on?->toDateString())->toBe('2026-12-01')
        ->and($task->priority)->toBe(TaskPriority::High)
        ->and($task->labels->modelKeys())->toEqualCanonicalizing([$bug->id, $design->id]);
});

test('only the fields sent change', function (): void {
    [$user, $project] = boardOwner();
    $label = Label::factory()->for($project)->create();
    $task = Task::factory()->for($project->columns()->firstOrFail())->create([
        'title' => 'Keep me',
        'description' => 'Notes',
        'assignee_id' => $user->id,
        'priority' => TaskPriority::Low,
    ]);
    $task->labels()->attach($label);

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', [$project, $task]), ['due_on' => '2026-12-24'])
        ->assertSessionHasNoErrors();

    $task->refresh();

    expect($task->title)->toBe('Keep me')
        ->and($task->description)->toBe('Notes')
        ->and($task->assignee_id)->toBe($user->id)
        ->and($task->priority)->toBe(TaskPriority::Low)
        ->and($task->due_on?->toDateString())->toBe('2026-12-24')
        ->and($task->labels->modelKeys())->toBe([$label->id]);
});

test('details are cleared with empty values', function (): void {
    [$user, $project] = boardOwner();
    $label = Label::factory()->for($project)->create();
    $task = Task::factory()->for($project->columns()->firstOrFail())->create([
        'description' => 'Notes',
        'assignee_id' => $user->id,
        'due_on' => '2026-12-01',
        'priority' => TaskPriority::Urgent,
    ]);
    $task->labels()->attach($label);

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', [$project, $task]), [
            'description' => '   ',
            'assignee_id' => null,
            'due_on' => null,
            'priority' => null,
            'labels' => [],
        ])
        ->assertSessionHasNoErrors();

    $task->refresh();

    expect($task->description)->toBeNull()
        ->and($task->assignee_id)->toBeNull()
        ->and($task->due_on)->toBeNull()
        ->and($task->priority)->toBeNull()
        ->and($task->labels)->toBeEmpty();
});

test('a card’s details are checked', function (string $field, mixed $value): void {
    [$user, $project] = boardOwner();
    $task = Task::factory()->for($project->columns()->firstOrFail())->create(['title' => 'Before']);

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', [$project, $task]), [$field => value($value)])
        ->assertSessionHasErrors(str_starts_with($field, 'labels') ? 'labels.0' : $field);

    expect($task->refresh()->title)->toBe('Before');
})->with([
    'blank title' => ['title', '  '],
    'long title' => ['title', str_repeat('a', 201)],
    'long description' => ['description', str_repeat('a', 10_001)],
    'assignee outside the workspace' => ['assignee_id', fn (): int => User::factory()->create()->id],
    'unknown assignee' => ['assignee_id', 999_999],
    'malformed date' => ['due_on', '01/12/2026'],
    'unknown priority' => ['priority', 'whenever'],
    'label of another project' => ['labels', fn (): array => [Label::factory()->create()->id]],
]);

test('members delete a card', function (): void {
    [$user, $project] = boardOwner();
    $task = Task::factory()->for($project->columns()->firstOrFail())->create();
    $task->labels()->attach(Label::factory()->for($project)->create());

    $this->actingAs($user)
        ->delete(route('projects.tasks.destroy', [$project, $task]))
        ->assertSessionHasNoErrors();

    expect(Task::query()->count())->toBe(0)
        ->and(Label::query()->count())->toBe(1);
});

test('an archived project’s card details cannot change', function (): void {
    [$user, $project] = boardOwner();
    $task = Task::factory()->for($project->columns()->firstOrFail())->create(['title' => 'Frozen']);
    $project->update(['archived_at' => now()]);

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', [$project, $task]), ['title' => 'Thawed'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('projects.tasks.destroy', [$project, $task]))
        ->assertForbidden();

    expect($task->refresh()->title)->toBe('Frozen');
});

test('cards of other projects and workspaces are out of reach', function (): void {
    [$user, $project] = boardOwner();
    $sibling = Project::factory()->for($project->workspace)->create();
    $siblingTask = Task::factory()->for($sibling->columns()->firstOrFail())->create(['title' => 'Sibling']);
    $foreign = Project::factory()->create();
    $foreignTask = Task::factory()->for($foreign->columns()->firstOrFail())->create(['title' => 'Foreign']);

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', [$project, $siblingTask]), ['title' => 'Mine'])
        ->assertNotFound();

    $this->actingAs($user)
        ->patch(route('projects.tasks.update', [$foreign, $foreignTask]), ['title' => 'Mine'])
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('projects.tasks.destroy', [$foreign, $foreignTask]))
        ->assertNotFound();

    expect($siblingTask->refresh()->title)->toBe('Sibling')
        ->and($foreignTask->refresh()->title)->toBe('Foreign');
});

test('the board carries each card’s details, and the page the people and labels to pick from', function (): void {
    [$user, $project] = boardOwner();
    $label = Label::factory()->for($project)->create(['name' => 'Bug', 'color' => 'red']);
    $task = Task::factory()->for($project->columns()->firstOrFail())->create([
        'created_by' => $user->id,
        'assignee_id' => $user->id,
        'due_on' => '2026-12-01',
        'priority' => TaskPriority::Medium,
    ]);
    $task->labels()->attach($label);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('columns.0.tasks.0.id', $task->id)
            ->where('columns.0.tasks.0.assigneeId', $user->id)
            ->where('columns.0.tasks.0.dueOn', '2026-12-01')
            ->where('columns.0.tasks.0.priority', 'medium')
            ->where('columns.0.tasks.0.labelIds', [$label->id])
            ->where('columns.0.tasks.0.createdBy', $user->name)
            ->where('labels', [['id' => $label->id, 'name' => 'Bug', 'color' => 'red']])
            ->has('members', 2)
            ->where('priorities', ['low', 'medium', 'high', 'urgent']));
});

test('a member who leaves the workspace leaves their cards unassigned', function (): void {
    [$user, $project] = boardOwner();
    $teammate = projectMember($project->workspace);
    $theirs = Task::factory()->for($project->columns()->firstOrFail())->create(['assignee_id' => $teammate->id]);
    $mine = Task::factory()->for($project->columns()->firstOrFail())->create(['assignee_id' => $user->id]);
    $elsewhere = Task::factory()->create(['assignee_id' => $teammate->id]);

    resolve(RemoveMember::class)->handle($project->workspace, $teammate);

    expect($theirs->refresh()->assignee_id)->toBeNull()
        ->and($mine->refresh()->assignee_id)->toBe($user->id)
        ->and($elsewhere->refresh()->assignee_id)->toBe($teammate->id);
});
