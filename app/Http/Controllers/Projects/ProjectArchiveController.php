<?php

namespace App\Http\Controllers\Projects;

use App\Http\Attributes\CurrentWorkspace;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Archiving hides a project from the sidebar and the list without losing anything; restoring brings it back.
 */
class ProjectArchiveController extends Controller
{
    public function store(#[CurrentWorkspace] Workspace $workspace, Project $project): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('archive', $project);

        if (! $project->isArchived()) {
            $project->update(['archived_at' => now()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was archived.', ['name' => $project->name])]);

        return to_route('projects.index');
    }

    public function destroy(#[CurrentWorkspace] Workspace $workspace, Project $project): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('archive', $project);

        $project->update(['archived_at' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name is back.', ['name' => $project->name])]);

        return to_route('projects.show', $project);
    }

    private function ensureInWorkspace(Workspace $workspace, Project $project): void
    {
        abort_unless($project->workspace_id === $workspace->id, 404);
        $project->setRelation('workspace', $workspace);
    }
}
