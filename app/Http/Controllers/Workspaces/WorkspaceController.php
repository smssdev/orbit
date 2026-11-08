<?php

namespace App\Http\Controllers\Workspaces;

use App\Actions\Workspaces\CreateWorkspace;
use App\Http\Attributes\CurrentWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspaces\SaveWorkspaceRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('workspaces/Create');
    }

    public function store(SaveWorkspaceRequest $request, #[CurrentUser] User $user, CreateWorkspace $createWorkspace): RedirectResponse
    {
        $workspace = $createWorkspace->handle($user, $request->string('name')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Welcome to :name.', ['name' => $workspace->name])]);

        return to_route('dashboard');
    }

    public function edit(#[CurrentWorkspace] Workspace $workspace, #[CurrentUser] User $user): Response
    {
        return Inertia::render('workspaces/Settings', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
            ],
            'canUpdate' => $user->can('update', $workspace),
        ]);
    }

    public function update(SaveWorkspaceRequest $request, #[CurrentWorkspace] Workspace $workspace): RedirectResponse
    {
        Gate::authorize('update', $workspace);

        $workspace->update(['name' => $request->string('name')->toString()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace updated.')]);

        return back();
    }
}
