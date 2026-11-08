<?php

namespace App\Http\Attributes;

use App\Models\User;
use App\Models\Workspace;
use Attribute;
use Illuminate\Contracts\Container\ContextualAttribute;
use Illuminate\Support\Facades\Auth;

/**
 * Injects the authenticated user's current workspace, like Laravel's #[CurrentUser].
 *
 * Routes using it sit behind the EnsureCurrentWorkspace middleware, which guarantees one is set.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class CurrentWorkspace implements ContextualAttribute
{
    public static function resolve(self $attribute): Workspace
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->currentWorkspace instanceof Workspace) {
            abort(404);
        }

        return $user->currentWorkspace;
    }
}
