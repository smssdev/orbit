<?php

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final class InviteMember
{
    /**
     * Invite an email address to the workspace and email them a link to join.
     *
     * Inviting an address that already has a pending invitation replaces it with a fresh one,
     * so "resend" needs no special handling and old links stop working.
     */
    public function handle(Workspace $workspace, User $inviter, string $email, WorkspaceRole $role): WorkspaceInvitation
    {
        $token = Str::random(64);

        $invitation = $workspace->invitations()->updateOrCreate(
            ['email' => Str::lower($email)],
            [
                'role' => $role,
                'token_hash' => WorkspaceInvitation::hashToken($token),
                'invited_by_id' => $inviter->id,
                'expires_at' => now()->addDays(WorkspaceInvitation::EXPIRES_AFTER_DAYS),
            ],
        );

        Notification::route('mail', $invitation->email)
            ->notify(new WorkspaceInvitationNotification($invitation, $token));

        return $invitation;
    }
}
