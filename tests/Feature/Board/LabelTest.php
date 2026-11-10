<?php

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;

test('members add a label to the project', function (): void {
    [$user, $project] = boardOwner();

    $this->actingAs($user)
        ->from(route('projects.show', $project))
        ->post(route('projects.labels.store', $project), ['name' => '  Bug  ', 'color' => 'red'])
        ->assertRedirect(route('projects.show', $project))
        ->assertSessionHasNoErrors();

    $label = Label::query()->sole();

    expect($label->project_id)->toBe($project->id)
        ->and($label->name)->toBe('Bug')
        ->and($label->color->value)->toBe('red');
});

test('a label needs a unique name and a known colour', function (array $data, string $field): void {
    [$user, $project] = boardOwner();
    Label::factory()->for($project)->create(['name' => 'Bug']);
    // The same name in another project is fine.
    Label::factory()->create(['name' => 'Design']);

    $this->actingAs($user)
        ->post(route('projects.labels.store', $project), $data)
        ->assertSessionHasErrors($field);

    expect($project->labels()->count())->toBe(1);
})->with([
    'no name' => [['name' => '', 'color' => 'red'], 'name'],
    'long name' => [['name' => str_repeat('a', 31), 'color' => 'red'], 'name'],
    'taken name' => [['name' => 'Bug', 'color' => 'blue'], 'name'],
    'unknown colour' => [['name' => 'Docs', 'color' => 'plaid'], 'color'],
]);

test('a project holds a limited number of labels', function (): void {
    [$user, $project] = boardOwner();
    Label::factory()->for($project)->count(Label::MAX_PER_PROJECT)->create();

    $this->actingAs($user)
        ->post(route('projects.labels.store', $project), ['name' => 'One more', 'color' => 'red'])
        ->assertSessionHasErrors('name');

    expect($project->labels()->count())->toBe(Label::MAX_PER_PROJECT);
});

test('deleting a label takes it off its cards', function (): void {
    [$user, $project] = boardOwner();
    $label = Label::factory()->for($project)->create();
    $task = Task::factory()->for($project->columns()->firstOrFail())->create();
    $task->labels()->attach($label);

    $this->actingAs($user)
        ->delete(route('projects.labels.destroy', [$project, $label]))
        ->assertSessionHasNoErrors();

    expect(Label::query()->count())->toBe(0)
        ->and($task->labels()->count())->toBe(0)
        ->and(Task::query()->count())->toBe(1);
});

test('labels of an archived project, another project or another workspace cannot change', function (): void {
    [$user, $project] = boardOwner();
    $sibling = Label::factory()->for(Project::factory()->for($project->workspace))->create();
    $foreignProject = Project::factory()->create();
    $foreign = Label::factory()->for($foreignProject)->create();

    $this->actingAs($user)
        ->delete(route('projects.labels.destroy', [$project, $sibling]))
        ->assertNotFound();

    $this->actingAs($user)
        ->post(route('projects.labels.store', $foreignProject), ['name' => 'Sneaky', 'color' => 'red'])
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('projects.labels.destroy', [$foreignProject, $foreign]))
        ->assertNotFound();

    $project->update(['archived_at' => now()]);

    $this->actingAs($user)
        ->post(route('projects.labels.store', $project), ['name' => 'Late', 'color' => 'red'])
        ->assertForbidden();

    expect(Label::query()->count())->toBe(2);
});
