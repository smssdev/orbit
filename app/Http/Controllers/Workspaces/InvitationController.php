<?php

namespace App\Http\Controllers\Workspaces;

use App\Actions\Workspaces\InviteMember;
use App\Http\Attributes\CurrentWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspaces\InviteMemberRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function store(
        InviteMemberRequest $request,
        #[CurrentWorkspace] Workspace $workspace,
        #[CurrentUser] User $user,
        InviteMember $inviteMember,
    ): RedirectResponse {
        $role = $request->role();

        Gate::authorize('manageMembers', $workspace);
        Gate::authorize('assignRole', [$workspace, $role]);

        $invitation = $inviteMember->handle($workspace, $user, $request->string('email')->toString(), $role);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent to :email.', ['email' => $invitation->email])]);

        return back();
    }

    public function destroy(#[CurrentWorkspace] Workspace $workspace, WorkspaceInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->workspace_id === $workspace->id, 404);

        Gate::authorize('manageMembers', $workspace);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation revoked.')]);

        return back();
    }
}
