<?php

namespace App\Actions\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Support\Facades\DB;

final class AcceptInvitation
{
    /**
     * Add the user to the invitation's workspace, consume the invitation, and switch them into the workspace.
     */
    public function handle(WorkspaceInvitation $invitation, User $user): Workspace
    {
        return DB::transaction(function () use ($invitation, $user): Workspace {
            $workspace = $invitation->workspace;

            // Someone who is already a member keeps their current role; an invitation never demotes.
            if (! $workspace->hasMember($user)) {
                $workspace->addMember($user, $invitation->role);
            }

            $invitation->delete();
            $user->switchWorkspace($workspace);

            return $workspace;
        });
    }
}
