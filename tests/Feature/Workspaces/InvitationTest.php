<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    Notification::fake();

    $this->owner = User::factory()->withWorkspace('Acme')->create();
    $this->workspace = $this->owner->currentWorkspace;

    $this->joinAs = function (WorkspaceRole $role): User {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);
        $user->switchWorkspace($this->workspace);

        return $user;
    };
});

test('the owner can invite someone by email', function (): void {
    $this->actingAs($this->owner)
        ->post(route('workspace.invitations.store'), ['email' => 'New.Person@Example.com', 'role' => 'admin'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $invitation = WorkspaceInvitation::query()->sole();

    expect($invitation->email)->toBe('new.person@example.com')
        ->and($invitation->role)->toBe(WorkspaceRole::Admin)
        ->and($invitation->invited_by_id)->toBe($this->owner->id)
        ->and($invitation->expires_at->isFuture())->toBeTrue();

    Notification::assertSentOnDemand(
        WorkspaceInvitationNotification::class,
        // The emailed token is the one whose hash is stored; the plain token itself is never saved.
        fn (WorkspaceInvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'new.person@example.com'
            && WorkspaceInvitation::hashToken($notification->token) === $invitation->token_hash
            && $notification->token !== $invitation->token_hash,
    );
});

test('the invitation email links to the accept page', function (): void {
    $invitation = WorkspaceInvitation::factory()->for($this->workspace)->for($this->owner, 'inviter')->create();

    $mail = new WorkspaceInvitationNotification($invitation, 'plain-token')->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toContain('Acme')
        ->and($mail->actionUrl)->toBe(route('invitations.show', 'plain-token'));
});

test('inviting the same address again replaces the old invitation', function (): void {
    $this->actingAs($this->owner)->post(route('workspace.invitations.store'), ['email' => 'sam@example.com', 'role' => 'member']);
    $firstHash = WorkspaceInvitation::query()->sole()->token_hash;

    $this->actingAs($this->owner)->post(route('workspace.invitations.store'), ['email' => 'SAM@example.com', 'role' => 'admin']);

    $invitation = WorkspaceInvitation::query()->sole();

    expect($invitation->token_hash)->not->toBe($firstHash)
        ->and($invitation->role)->toBe(WorkspaceRole::Admin);
});

test('existing members cannot be invited', function (): void {
    $member = ($this->joinAs)(WorkspaceRole::Member);

    $this->actingAs($this->owner)
        ->post(route('workspace.invitations.store'), ['email' => strtoupper($member->email), 'role' => 'member'])
        ->assertSessionHasErrors('email');

    expect(WorkspaceInvitation::query()->count())->toBe(0);
});

test('invitations need a valid email and an assignable role', function (array $data, string $error): void {
    $this->actingAs($this->owner)
        ->post(route('workspace.invitations.store'), $data)
        ->assertSessionHasErrors($error);
})->with([
    'missing email' => [['role' => 'member'], 'email'],
    'invalid email' => [['email' => 'not-an-email', 'role' => 'member'], 'email'],
    'owner role' => [['email' => 'sam@example.com', 'role' => 'owner'], 'role'],
    'unknown role' => [['email' => 'sam@example.com', 'role' => 'superuser'], 'role'],
]);

test('admins can invite members but not admins', function (): void {
    $admin = ($this->joinAs)(WorkspaceRole::Admin);

    $this->actingAs($admin)
        ->post(route('workspace.invitations.store'), ['email' => 'a@example.com', 'role' => 'admin'])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('workspace.invitations.store'), ['email' => 'm@example.com', 'role' => 'member'])
        ->assertRedirect();

    expect(WorkspaceInvitation::query()->pluck('email')->all())->toBe(['m@example.com']);
});

test('members cannot invite anyone', function (): void {
    $member = ($this->joinAs)(WorkspaceRole::Member);

    $this->actingAs($member)
        ->post(route('workspace.invitations.store'), ['email' => 'sam@example.com', 'role' => 'member'])
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('managers can revoke an invitation', function (): void {
    $invitation = WorkspaceInvitation::factory()->for($this->workspace)->create();

    $this->actingAs($this->owner)
        ->delete(route('workspace.invitations.destroy', $invitation))
        ->assertRedirect();

    expect($invitation->fresh())->toBeNull();
});

test('members cannot revoke invitations', function (): void {
    $member = ($this->joinAs)(WorkspaceRole::Member);
    $invitation = WorkspaceInvitation::factory()->for($this->workspace)->create();

    $this->actingAs($member)
        ->delete(route('workspace.invitations.destroy', $invitation))
        ->assertForbidden();
});

test('invitations of another workspace cannot be revoked', function (): void {
    $invitation = WorkspaceInvitation::factory()->create();

    $this->actingAs($this->owner)
        ->delete(route('workspace.invitations.destroy', $invitation))
        ->assertNotFound();

    expect($invitation->fresh())->not->toBeNull();
});

test('guests following an invitation link are asked to sign in', function (): void {
    $this->get(route('invitations.show', 'any-token'))->assertRedirect(route('login'));
});

test('the invitation page shows the invitation and its status', function (string $status): void {
    $invitee = User::factory()->create(['email' => 'sam@example.com']);
    $factory = WorkspaceInvitation::factory()->for($this->workspace)->for($this->owner, 'inviter')->withToken('secret-token');
    $factory = $status === 'expired' ? $factory->expired() : $factory;
    $factory->create(['email' => $status === 'wrong-account' ? 'someone-else@example.com' : 'sam@example.com']);

    $this->actingAs($invitee)
        ->get(route('invitations.show', 'secret-token'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('invitations/Show')
            ->where('invitation.workspace', 'Acme')
            ->where('invitation.invitedBy', $this->owner->name)
            ->where('status', $status),
        );
})->with(['valid', 'expired', 'wrong-account']);

test('unknown invitation tokens return not found', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('invitations.show', 'nope'))->assertNotFound();
    $this->actingAs($user)->post(route('invitations.accept', 'nope'))->assertNotFound();
});

test('accepting an invitation joins the workspace and switches into it', function (): void {
    $invitee = User::factory()->withWorkspace('Personal')->create(['email' => 'sam@example.com']);
    $invitation = WorkspaceInvitation::factory()->for($this->workspace)->withToken('secret-token')
        ->create(['email' => 'Sam@Example.com', 'role' => WorkspaceRole::Admin]);

    $this->actingAs($invitee)
        ->post(route('invitations.accept', 'secret-token'))
        ->assertRedirect(route('dashboard'));

    expect($this->workspace->roleOf($invitee))->toBe(WorkspaceRole::Admin)
        ->and($invitee->fresh()?->current_workspace_id)->toBe($this->workspace->id)
        ->and($invitation->fresh())->toBeNull();
});

test('accepting never demotes an existing member', function (): void {
    $admin = ($this->joinAs)(WorkspaceRole::Admin);
    WorkspaceInvitation::factory()->for($this->workspace)->withToken('secret-token')
        ->create(['email' => $admin->email, 'role' => WorkspaceRole::Member]);

    $this->actingAs($admin)->post(route('invitations.accept', 'secret-token'))->assertRedirect();

    expect($this->workspace->roleOf($admin))->toBe(WorkspaceRole::Admin);
});

test('expired invitations cannot be accepted', function (): void {
    $invitee = User::factory()->create(['email' => 'sam@example.com']);
    WorkspaceInvitation::factory()->for($this->workspace)->withToken('secret-token')->expired()
        ->create(['email' => 'sam@example.com']);

    $this->actingAs($invitee)->post(route('invitations.accept', 'secret-token'))->assertStatus(410);

    expect($this->workspace->hasMember($invitee))->toBeFalse();
});

test('invitations can only be accepted by the invited address', function (): void {
    $someoneElse = User::factory()->create(['email' => 'eve@example.com']);
    WorkspaceInvitation::factory()->for($this->workspace)->withToken('secret-token')
        ->create(['email' => 'sam@example.com']);

    $this->actingAs($someoneElse)->post(route('invitations.accept', 'secret-token'))->assertForbidden();

    expect($this->workspace->hasMember($someoneElse))->toBeFalse();
});
