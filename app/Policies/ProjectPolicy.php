<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

/**
 * Every member sees and creates projects. The project's creator and the workspace's owners and admins edit and
 * archive it; only owners and admins delete one, and only once it is archived, so nothing goes in one click.
 */
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->workspace->hasMember($user);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function update(User $user, Project $project): bool
    {
        $role = $project->workspace->roleOf($user);

        if ($role === null) {
            return false;
        }

        return $role->canManageMembers() || $project->created_by === $user->id;
    }

    public function archive(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->isArchived() && ($project->workspace->roleOf($user)?->canManageMembers() ?? false);
    }
}
