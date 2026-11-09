<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * A user in the workspace with the given role, switched into it.
 */
function projectMember(Workspace $workspace, WorkspaceRole $role = WorkspaceRole::Member): User
{
    $user = User::factory()->create();
    $workspace->addMember($user, $role);
    $user->switchWorkspace($workspace);

    return $user;
}
