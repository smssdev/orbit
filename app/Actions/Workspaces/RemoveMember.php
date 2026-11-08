<?php

namespace App\Actions\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final class RemoveMember
{
    /**
     * Remove the member from the workspace. Also used when a member leaves on their own.
     */
    public function handle(Workspace $workspace, User $member): void
    {
        DB::transaction(function () use ($workspace, $member): void {
            $workspace->members()->detach($member->id);

            if ($member->current_workspace_id === $workspace->id) {
                $member->switchWorkspace($member->workspaces()->oldest('user_workspace.created_at')->first());
            }
        });
    }
}
