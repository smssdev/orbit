<?php

namespace App\Http\Controllers\Workspaces;

use App\Actions\Workspaces\AcceptInvitation;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInvitationController extends Controller
{
    public function show(string $token, #[CurrentUser] User $user): Response
    {
        $invitation = $this->findOrFail($token);

        return Inertia::render('invitations/Show', [
            'token' => $token,
            'invitation' => [
                'workspace' => $invitation->workspace->name,
                'invitedBy' => $invitation->inviter?->name,
                'email' => $invitation->email,
                'role' => $invitation->role,
            ],
            'status' => match (true) {
                $invitation->isExpired() => 'expired',
                ! $invitation->isFor($user) => 'wrong-account',
                default => 'valid',
            },
        ]);
    }

    public function store(string $token, #[CurrentUser] User $user, AcceptInvitation $acceptInvitation): RedirectResponse
    {
        $invitation = $this->findOrFail($token);

        abort_if($invitation->isExpired(), 410, __('This invitation has expired.'));
        abort_unless($invitation->isFor($user), 403, __('This invitation was sent to a different email address.'));

        $workspace = $acceptInvitation->handle($invitation, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Welcome to :name.', ['name' => $workspace->name])]);

        return to_route('dashboard');
    }

    private function findOrFail(string $token): WorkspaceInvitation
    {
        $invitation = WorkspaceInvitation::findByToken($token);

        abort_if(! $invitation instanceof WorkspaceInvitation, 404);

        return $invitation->load('workspace', 'inviter');
    }
}
