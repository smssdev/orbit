<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->owner = User::factory()->create(['name' => 'Olivia Owner']);
    $this->workspace = Workspace::factory()->for($this->owner, 'owner')->create();
    $this->owner->switchWorkspace($this->workspace);

    $this->admin = User::factory()->create(['name' => 'Adam Admin']);
    $this->workspace->addMember($this->admin, WorkspaceRole::Admin);
    $this->admin->switchWorkspace($this->workspace);

    $this->member = User::factory()->create(['name' => 'Mona Member']);
    $this->workspace->addMember($this->member, WorkspaceRole::Member);
    $this->member->switchWorkspace($this->workspace);
});

test('the members page lists members with what the viewer may do to each', function (): void {
    $this->actingAs($this->admin)
        ->get(route('workspace.members.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('workspaces/Members')
            ->has('members', 3)
            ->where('members.0.id', $this->owner->id)
            ->where('members.0.role', 'owner')
            ->where('members.0.can.remove', false)
            ->where('members.1.isYou', true)
            ->where('members.1.can.remove', true)
            ->where('members.1.can.updateRole', false)
            ->where('members.2.role', 'member')
            ->where('members.2.can.updateRole', true)
            ->where('canManageMembers', true)
            ->where('assignableRoles', ['member']),
        );
});

test('the owner can promote a member to admin', function (): void {
    $this->actingAs($this->owner)
        ->patch(route('workspace.members.update', $this->member), ['role' => 'admin'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($this->workspace->roleOf($this->member))->toBe(WorkspaceRole::Admin);
});

test('admins cannot promote anyone to admin', function (): void {
    $this->actingAs($this->admin)
        ->patch(route('workspace.members.update', $this->member), ['role' => 'admin'])
        ->assertForbidden();

    expect($this->workspace->roleOf($this->member))->toBe(WorkspaceRole::Member);
});

test('admins cannot change another admin', function (): void {
    $otherAdmin = User::factory()->create();
    $this->workspace->addMember($otherAdmin, WorkspaceRole::Admin);

    $this->actingAs($this->admin)
        ->patch(route('workspace.members.update', $otherAdmin), ['role' => 'member'])
        ->assertForbidden();
});

test('members cannot change roles', function (): void {
    $this->actingAs($this->member)
        ->patch(route('workspace.members.update', $this->admin), ['role' => 'member'])
        ->assertForbidden();
});

test('nobody can change their own role', function (): void {
    $this->actingAs($this->owner)
        ->patch(route('workspace.members.update', $this->owner), ['role' => 'member'])
        ->assertForbidden();
});

test('ownership cannot be handed out', function (): void {
    $this->actingAs($this->owner)
        ->patch(route('workspace.members.update', $this->admin), ['role' => 'owner'])
        ->assertSessionHasErrors('role');
});

test('changing the role of a non-member returns not found', function (): void {
    $stranger = User::factory()->create();

    $this->actingAs($this->owner)
        ->patch(route('workspace.members.update', $stranger), ['role' => 'member'])
        ->assertNotFound();
});

test('the owner can remove an admin, who is moved out of the workspace', function (): void {
    $this->actingAs($this->owner)
        ->delete(route('workspace.members.destroy', $this->admin))
        ->assertRedirect();

    expect($this->workspace->hasMember($this->admin))->toBeFalse()
        ->and($this->admin->fresh()?->current_workspace_id)->toBeNull();
});

test('admins can remove members but not the owner', function (): void {
    $this->actingAs($this->admin)->delete(route('workspace.members.destroy', $this->owner))->assertForbidden();
    $this->actingAs($this->admin)->delete(route('workspace.members.destroy', $this->member))->assertRedirect();

    expect($this->workspace->hasMember($this->owner))->toBeTrue()
        ->and($this->workspace->hasMember($this->member))->toBeFalse();
});

test('members can leave and land in another workspace of theirs', function (): void {
    $personal = Workspace::factory()->withMember($this->member)->create();

    $this->actingAs($this->member)
        ->delete(route('workspace.members.destroy', $this->member))
        ->assertRedirect(route('dashboard'));

    expect($this->workspace->hasMember($this->member))->toBeFalse()
        ->and($this->member->fresh()?->current_workspace_id)->toBe($personal->id);
});

test('the owner cannot leave their own workspace', function (): void {
    $this->actingAs($this->owner)
        ->delete(route('workspace.members.destroy', $this->owner))
        ->assertForbidden();

    expect($this->workspace->hasMember($this->owner))->toBeTrue();
});
