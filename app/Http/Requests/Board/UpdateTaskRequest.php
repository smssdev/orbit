<?php

namespace App\Http\Requests\Board;

use App\Enums\TaskPriority;
use App\Models\Label;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Changes some of a card's details: only the fields sent are validated and saved, so the panel saves each field on
 * its own.
 */
class UpdateTaskRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->project();

        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            // Only a member of the project's workspace can take a card.
            'assignee_id' => ['sometimes', 'nullable', 'integer', Rule::exists('user_workspace', 'user_id')->where('workspace_id', $project->workspace_id)],
            'due_on' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'priority' => ['sometimes', 'nullable', Rule::enum(TaskPriority::class)],
            'labels' => ['sometimes', 'array', 'max:'.Label::MAX_PER_PROJECT],
            'labels.*' => ['integer', 'distinct', Rule::exists('labels', 'id')->where('project_id', $project->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assignee_id.exists' => __('That person is no longer in this workspace.'),
            'labels.*.exists' => __('That label is no longer in this project.'),
        ];
    }

    /**
     * The task's attributes that were sent, ready to save.
     *
     * @return array<string, mixed>
     */
    public function changes(): array
    {
        $changes = [];

        if ($this->has('title')) {
            $changes['title'] = $this->string('title')->trim()->toString();
        }

        if ($this->has('description')) {
            $description = $this->string('description')->trim()->toString();
            $changes['description'] = $description === '' ? null : $description;
        }

        if ($this->has('assignee_id')) {
            $changes['assignee_id'] = $this->filled('assignee_id') ? $this->integer('assignee_id') : null;
        }

        if ($this->has('due_on')) {
            $changes['due_on'] = $this->filled('due_on') ? $this->string('due_on')->toString() : null;
        }

        if ($this->has('priority')) {
            $changes['priority'] = $this->enum('priority', TaskPriority::class);
        }

        return $changes;
    }

    /**
     * The label IDs to put on the card, or null when they were not sent.
     *
     * @return list<int>|null
     */
    public function labelIds(): ?array
    {
        if (! $this->has('labels')) {
            return null;
        }

        return array_values(array_map(intval(...), array_filter((array) $this->input('labels'), is_numeric(...))));
    }

    private function project(): Project
    {
        $project = $this->route('project');

        abort_unless($project instanceof Project, 404);

        return $project;
    }
}
