<?php

namespace App\Http\Requests\Projects;

use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProjectRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', Rule::enum(ProjectColor::class)],
            'icon' => ['required', Rule::enum(ProjectIcon::class)],
        ];
    }

    /**
     * @return array{name: string, description: string|null, color: ProjectColor, icon: ProjectIcon}
     */
    public function project(): array
    {
        $description = $this->string('description')->trim()->toString();

        return [
            'name' => $this->string('name')->trim()->toString(),
            'description' => $description === '' ? null : $description,
            'color' => $this->enum('color', ProjectColor::class) ?? ProjectColor::Slate,
            'icon' => $this->enum('icon', ProjectIcon::class) ?? ProjectIcon::Folder,
        ];
    }
}
