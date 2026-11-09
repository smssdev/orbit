<?php

namespace App\Http\Controllers\Board;

use App\Http\Attributes\CurrentWorkspace;
use App\Http\Controllers\Concerns\ScopesProjectsToWorkspace;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use App\Support\Positions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Moves a card into a column (`column`), right before another card of it (`before`), or to its bottom when `before`
 * is empty. Within its column or across columns, it is the same move.
 */
class TaskPositionController extends Controller
{
    use ScopesProjectsToWorkspace;

    public function update(Request $request, #[CurrentWorkspace] Workspace $workspace, Project $project, Task $task): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('editBoard', $project);

        $project->changeBoard(function () use ($request, $project, $task): void {
            // Checked under the lock, so the column and the neighbour are still where the client saw them.
            $request->validate([
                'column' => ['required', 'integer', Rule::exists('columns', 'id')->where('project_id', $project->id)],
                'before' => [
                    'nullable',
                    'integer',
                    Rule::notIn([$task->id]),
                    Rule::exists('tasks', 'id')->where('column_id', $request->integer('column')),
                ],
            ], [
                'column.exists' => __('The board changed in the meantime. It is up to date now.'),
                'before.exists' => __('The board changed in the meantime. It is up to date now.'),
            ]);

            $column = $request->integer('column');
            $siblings = Task::query()->where('column_id', $column)->whereKeyNot($task->id);
            $before = $request->filled('before') ? $request->integer('before') : null;

            $task->update(['column_id' => $column, 'position' => Positions::before($siblings, $before)]);
        });

        return back();
    }
}
