<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes sure the user has a current workspace they still belong to.
 *
 * Falls back to their oldest workspace when the current one is gone, and sends users
 * with no workspace at all to create one.
 */
class EnsureCurrentWorkspace
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $current = $user->currentWorkspace;

        if ($current instanceof Workspace && $current->hasMember($user)) {
            return $next($request);
        }

        $fallback = $user->workspaces()->oldest('user_workspace.created_at')->first();

        if (! $fallback instanceof Workspace) {
            return to_route('workspaces.create');
        }

        $user->switchWorkspace($fallback);

        return $next($request);
    }
}
