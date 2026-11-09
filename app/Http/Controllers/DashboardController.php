<?php

namespace App\Http\Controllers;

use App\Http\Attributes\CurrentWorkspace;
use App\Models\Column;
use App\Models\Project;
use App\Models\Workspace;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The current workspace at a glance: a few totals, and each active project's cards per column.
     */
    public function __invoke(#[CurrentWorkspace] Workspace $workspace): Response
    {
        $projects = $workspace->projects()
            ->active()
            ->withCount('tasks')
            ->with(['columns' => fn ($query) => $query->orderBy('position')->withCount('tasks')])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'projects' => $projects->count(),
                'tasks' => $projects->sum(fn (Project $project): int => (int) $project->tasks_count),
                'members' => $workspace->members()->count(),
                'invitations' => $workspace->invitations()->where('expires_at', '>', now())->count(),
            ],
            'projects' => $projects->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'color' => $project->color,
                'icon' => $project->icon,
                'tasks' => (int) $project->tasks_count,
                'columns' => $project->columns->map(fn (Column $column): array => [
                    'name' => $column->name,
                    'tasks' => (int) $column->tasks_count,
                ])->values(),
            ])->values(),
        ]);
    }
}
