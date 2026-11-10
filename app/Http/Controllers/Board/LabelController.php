<?php

namespace App\Http\Controllers\Board;

use App\Http\Attributes\CurrentWorkspace;
use App\Http\Controllers\Concerns\ScopesProjectsToWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Board\StoreLabelRequest;
use App\Models\Label;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * A project's labels. Every member who can edit the board adds them and takes them away.
 */
class LabelController extends Controller
{
    use ScopesProjectsToWorkspace;

    public function store(StoreLabelRequest $request, #[CurrentWorkspace] Workspace $workspace, Project $project): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('editBoard', $project);

        $project->changeBoard(function () use ($request, $project): void {
            if ($project->labels()->count() >= Label::MAX_PER_PROJECT) {
                throw ValidationException::withMessages([
                    'name' => __('A project holds up to :max labels.', ['max' => Label::MAX_PER_PROJECT]),
                ]);
            }

            $project->labels()->create($request->label());
        });

        return back();
    }

    /**
     * Deletes a label, and takes it off every card that wore it.
     */
    public function destroy(#[CurrentWorkspace] Workspace $workspace, Project $project, Label $label): RedirectResponse
    {
        $this->ensureInWorkspace($workspace, $project);
        Gate::authorize('editBoard', $project);

        $label->delete();

        return back();
    }
}
