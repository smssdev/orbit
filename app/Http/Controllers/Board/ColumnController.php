<?php

namespace App\Http\Controllers\Board;

use App\Http\Attributes\CurrentWorkspace;
use App\Http\Controllers\Concerns\ScopesProjectsToWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Board\SaveColumnRequest;
use App\Models\Column;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use App\Support\Positions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ColumnController extends Controller
{
    use ScopesProjectsToWorkspace;

    /**
     * Adds a column at the end of the board.
     */
    public function store(SaveColumnRequest $request, #[CurrentWorkspace] Workspace $workspace, Project $project): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('editBoard', $project);

        $project->changeBoard(function () use ($request, $project): void {
            $columns = Column::query()->whereBelongsTo($project);

            if ($columns->count() >= Column::MAX_PER_PROJECT) {
                throw ValidationException::withMessages([
                    'name' => __('A board holds up to :max columns.', ['max' => Column::MAX_PER_PROJECT]),
                ]);
            }

            $project->columns()->create([
                'name' => $request->name(),
                'position' => Positions::last($columns) + Positions::GAP,
            ]);
        });

        return back();
    }

    public function update(SaveColumnRequest $request, #[CurrentWorkspace] Workspace $workspace, Project $project, Column $column): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('editBoard', $project);

        $column->update(['name' => $request->name()]);

        return back();
    }

    /**
     * Deletes a column. One that still has cards needs another column of the board to take them, at its end and in
     * the same order, so deleting a column never deletes work.
     */
    public function destroy(Request $request, #[CurrentWorkspace] Workspace $workspace, Project $project, Column $column): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('editBoard', $project);

        $project->changeBoard(function () use ($request, $project, $column): void {
            $tasks = Task::query()->whereBelongsTo($column);

            $request->validate([
                'move_to' => [
                    Rule::requiredIf(fn (): bool => $tasks->exists()),
                    'nullable',
                    'integer',
                    Rule::exists('columns', 'id')->where('project_id', $project->id)->whereNot('id', $column->id),
                ],
            ], [
                'move_to.required' => __('Choose a column for its cards first.'),
                'move_to.exists' => __('That column is no longer on the board.'),
            ]);

            if ($request->filled('move_to')) {
                $target = Task::query()->where('column_id', $request->integer('move_to'));
                $position = Positions::last($target);

                foreach ((clone $tasks)->orderBy('position')->orderBy('id')->pluck('id') as $id) {
                    $position += Positions::GAP;
                    Task::query()->whereKey($id)->update(['column_id' => $request->integer('move_to'), 'position' => $position]);
                }
            }

            $column->delete();
        });

        return back();
    }
}
