<?php

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

final class UpdateMemberRole
{
    public function handle(Workspace $workspace, User $member, WorkspaceRole $role): void
    {
        $workspace->members()->updateExistingPivot($member->id, ['role' => $role->value]);
    }
}
