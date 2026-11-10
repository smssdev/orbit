<?php

namespace App\Http\Requests\Board;

use App\Enums\ProjectColor;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLabelRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->id : 0;

        return [
            'name' => ['required', 'string', 'max:30', Rule::unique('labels', 'name')->where('project_id', $projectId)],
            'color' => ['required', Rule::enum(ProjectColor::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => __('This project already has that label.'),
        ];
    }

    /**
     * @return array{name: string, color: ProjectColor}
     */
    public function label(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'color' => $this->enum('color', ProjectColor::class) ?? ProjectColor::Slate,
        ];
    }
}
