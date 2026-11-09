<?php

namespace App\Http\Controllers\Board;

use App\Http\Attributes\CurrentWorkspace;
use App\Http\Controllers\Concerns\ScopesProjectsToWorkspace;
use App\Http\Controllers\Controller;
use App\Models\Column;
use App\Models\Project;
use App\Models\Workspace;
use App\Support\Positions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Moves a column right before another one (`before`), or to the end of the board when `before` is empty.
 */
class ColumnPositionController extends Controller
{
    use ScopesProjectsToWorkspace;

    public function update(Request $request, #[CurrentWorkspace] Workspace $workspace, Project $project, Column $column): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('editBoard', $project);

        $project->changeBoard(function () use ($request, $project, $column): void {
            // Checked under the lock, so the neighbour is still where the client saw it.
            $request->validate([
                'before' => [
                    'nullable',
                    'integer',
                    Rule::notIn([$column->id]),
                    Rule::exists('columns', 'id')->where('project_id', $project->id),
                ],
            ], [
                'before.exists' => __('The board changed in the meantime. It is up to date now.'),
            ]);

            $siblings = Column::query()->whereBelongsTo($project)->whereKeyNot($column->id);
            $before = $request->filled('before') ? $request->integer('before') : null;

            $column->update(['position' => Positions::before($siblings, $before)]);
        });

        return back();
    }
}
