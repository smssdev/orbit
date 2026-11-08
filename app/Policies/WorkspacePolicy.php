<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->roleOf($user)?->canManageMembers() ?? false;
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $this->update($user, $workspace);
    }

    /**
     * Whether the user may give someone this role, by invitation or by changing an existing member's role.
     */
    public function assignRole(User $user, Workspace $workspace, WorkspaceRole $role): bool
    {
        return in_array($role, $this->assignableRoles($user, $workspace), true);
    }

    /**
     * A member can be managed by someone allowed to assign the role they hold today:
     * owners manage admins and members, admins manage members, and nobody manages the owner.
     */
    public function updateMember(User $user, Workspace $workspace, User $member): bool
    {
        if ($user->is($member)) {
            return false;
        }

        $memberRole = $workspace->roleOf($member);

        return $memberRole instanceof WorkspaceRole && $this->assignRole($user, $workspace, $memberRole);
    }

    /**
     * Managers remove members they can manage; anyone but the owner may remove themselves (leave).
     */
    public function removeMember(User $user, Workspace $workspace, User $member): bool
    {
        if ($user->is($member)) {
            $role = $workspace->roleOf($user);

            return $role instanceof WorkspaceRole && $role !== WorkspaceRole::Owner;
        }

        return $this->updateMember($user, $workspace, $member);
    }

    /**
     * @return list<WorkspaceRole>
     */
    private function assignableRoles(User $user, Workspace $workspace): array
    {
        return $workspace->roleOf($user)?->assignableRoles() ?? [];
    }
}
