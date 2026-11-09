<?php

namespace App\Http\Controllers\Board;

use App\Http\Attributes\CurrentWorkspace;
use App\Http\Controllers\Concerns\ScopesProjectsToWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Board\StoreTaskRequest;
use App\Models\Column;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Positions;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    use ScopesProjectsToWorkspace;

    /**
     * Adds a card at the bottom of a column.
     */
    public function store(StoreTaskRequest $request, #[CurrentWorkspace] Workspace $workspace, Project $project, Column $column, #[CurrentUser] User $user): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('editBoard', $project);

        $project->changeBoard(function () use ($request, $project, $column, $user): void {
            $project->tasks()->create([
                'column_id' => $column->id,
                'created_by' => $user->id,
                'title' => $request->title(),
                'position' => Positions::last(Task::query()->whereBelongsTo($column)) + Positions::GAP,
            ]);
        });

        return back();
    }
}
