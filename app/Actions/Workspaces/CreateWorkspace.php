<?php

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final class CreateWorkspace
{
    /**
     * Create a workspace owned by the user and switch them into it.
     */
    public function handle(User $owner, string $name): Workspace
    {
        return DB::transaction(function () use ($owner, $name): Workspace {
            $workspace = Workspace::create([
                'name' => $name,
                'owner_id' => $owner->id,
            ]);

            $workspace->addMember($owner, WorkspaceRole::Owner);
            $owner->switchWorkspace($workspace);

            return $workspace;
        });
    }
}
