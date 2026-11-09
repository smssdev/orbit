<?php

namespace App\Http\Controllers\Projects;

use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use App\Http\Attributes\CurrentWorkspace;
use App\Http\Controllers\Concerns\ScopesProjectsToWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\SaveProjectRequest;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use ScopesProjectsToWorkspace;

    public function index(Request $request, #[CurrentWorkspace] Workspace $workspace, #[CurrentUser] User $user): Response
    {
        $archived = $request->query('filter') === 'archived';

        $projects = $workspace->projects()
            ->when($archived, fn ($query) => $query->archived()->latest('archived_at'), fn ($query) => $query->active()->orderBy('name'))
            ->get();

        return Inertia::render('projects/Index', [
            'projects' => $projects->map(fn (Project $project): array => [
                ...$project->summary(),
                'can' => [
                    'update' => $user->can('update', $project),
                    'delete' => $user->can('delete', $project),
                ],
            ])->values(),
            'filter' => $archived ? 'archived' : 'active',
            'counts' => [
                'active' => $workspace->projects()->active()->count(),
                'archived' => $workspace->projects()->archived()->count(),
            ],
            'colors' => ProjectColor::cases(),
            'icons' => ProjectIcon::cases(),
        ]);
    }

    public function store(SaveProjectRequest $request, #[CurrentWorkspace] Workspace $workspace, #[CurrentUser] User $user): RedirectResponse
    {
        Gate::authorize('create', [Project::class, $workspace]);

        $project = $workspace->projects()->create([...$request->project(), 'created_by' => $user->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name is ready.', ['name' => $project->name])]);

        return to_route('projects.show', $project);
    }

    public function show(#[CurrentWorkspace] Workspace $workspace, Project $project, #[CurrentUser] User $user): Response
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('view', $project);

        return Inertia::render('projects/Show', [
            'project' => [
                ...$project->summary(),
                'createdBy' => $project->creator?->name,
                'createdAt' => $project->created_at?->toIso8601String(),
                'can' => [
                    'update' => $user->can('update', $project),
                    'delete' => $user->can('delete', $project),
                    'editBoard' => $user->can('editBoard', $project),
                ],
            ],
            'columns' => $project->board(...),
            'colors' => ProjectColor::cases(),
            'icons' => ProjectIcon::cases(),
        ]);
    }

    public function update(SaveProjectRequest $request, #[CurrentWorkspace] Workspace $workspace, Project $project): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('update', $project);

        $project->update($request->project());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return back();
    }

    public function destroy(#[CurrentWorkspace] Workspace $workspace, Project $project): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('delete', $project);

        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was deleted.', ['name' => $project->name])]);

        return to_route('projects.index', ['filter' => 'archived']);
    }
}
