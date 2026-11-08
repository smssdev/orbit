<?php

namespace App\Http\Controllers\Workspaces;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CurrentWorkspaceController extends Controller
{
    /**
     * Switch the user to another workspace they belong to.
     */
    public function update(Request $request, #[CurrentUser] User $user): RedirectResponse
    {
        $request->validate([
            'workspace_id' => ['required', 'integer'],
        ]);

        $workspace = Workspace::query()->findOrFail($request->integer('workspace_id'));

        Gate::authorize('view', $workspace);

        $user->switchWorkspace($workspace);

        return to_route('dashboard');
    }
}
