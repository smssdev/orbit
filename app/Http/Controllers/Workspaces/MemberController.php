<?php

namespace App\Http\Controllers\Workspaces;

use App\Actions\Workspaces\RemoveMember;
use App\Actions\Workspaces\UpdateMemberRole;
use App\Http\Attributes\CurrentWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspaces\UpdateMemberRoleRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(#[CurrentWorkspace] Workspace $workspace, #[CurrentUser] User $user): Response
    {
        // Loading members up front lets every policy check below reuse them instead of querying per row.
        $workspace->load([
            'members' => fn (BelongsToMany $query) => $query->orderBy('user_workspace.created_at'),
            'invitations' => fn ($query) => $query->with('inviter')->oldest(),
        ]);

        return Inertia::render('workspaces/Members', [
            'members' => $workspace->members->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->membership()->role,
                'joinedAt' => $member->membership()->created_at?->toIso8601String(),
                'isYou' => $member->is($user),
                'can' => [
                    'updateRole' => $user->can('updateMember', [$workspace, $member]),
                    'remove' => $user->can('removeMember', [$workspace, $member]),
                ],
            ])->values(),
            'invitations' => $workspace->invitations->map(fn (WorkspaceInvitation $invitation): array => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'invitedBy' => $invitation->inviter?->name,
                'expiresAt' => $invitation->expires_at->toIso8601String(),
                'isExpired' => $invitation->isExpired(),
            ])->values(),
            'canManageMembers' => $user->can('manageMembers', $workspace),
            'assignableRoles' => $workspace->roleOf($user)?->assignableRoles() ?? [],
        ]);
    }

    public function update(
        UpdateMemberRoleRequest $request,
        #[CurrentWorkspace] Workspace $workspace,
        User $member,
        UpdateMemberRole $updateMemberRole,
    ): RedirectResponse {
        abort_unless($workspace->hasMember($member), 404);

        $role = $request->role();

        Gate::authorize('updateMember', [$workspace, $member]);
        Gate::authorize('assignRole', [$workspace, $role]);

        $updateMemberRole->handle($workspace, $member, $role);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name is now :role.', [
            'name' => $member->name,
            'role' => $role->label(),
        ])]);

        return back();
    }

    public function destroy(
        #[CurrentWorkspace] Workspace $workspace,
        User $member,
        #[CurrentUser] User $user,
        RemoveMember $removeMember,
    ): RedirectResponse {
        abort_unless($workspace->hasMember($member), 404);

        Gate::authorize('removeMember', [$workspace, $member]);

        $removeMember->handle($workspace, $member);

        if ($member->is($user)) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('You left :name.', ['name' => $workspace->name])]);

            return to_route('dashboard');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was removed from the workspace.', ['name' => $member->name])]);

        return back();
    }
}
