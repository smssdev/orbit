<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Project;
use App\Models\Workspace;

trait ScopesProjectsToWorkspace
{
    /**
     * Projects are addressed by ID, so one from another workspace answers as if it did not exist.
     */
    protected function ensureInWorkspace(Workspace $workspace, Project $project): void
    {
        abort_unless($project->workspace_id === $workspace->id, 404);
        $project->setRelation('workspace', $workspace);
    }
}
