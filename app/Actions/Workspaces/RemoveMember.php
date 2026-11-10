<?php

namespace App\Actions\Workspaces;

use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final class RemoveMember
{
    /**
     * Remove the member from the workspace. Also used when a member leaves on their own. Their cards in the
     * workspace's projects go back to unassigned, since they can no longer see them.
     */
    public function handle(Workspace $workspace, User $member): void
    {
        DB::transaction(function () use ($workspace, $member): void {
            $workspace->members()->detach($member->id);

            Task::query()
                ->where('assignee_id', $member->id)
                ->whereIn('project_id', $workspace->projects()->select('id'))
                ->update(['assignee_id' => null]);

            if ($member->current_workspace_id === $workspace->id) {
                $member->switchWorkspace($member->workspaces()->oldest('user_workspace.created_at')->first());
            }
        });
    }
}
