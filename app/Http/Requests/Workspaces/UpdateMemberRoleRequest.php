<?php

namespace App\Http\Requests\Workspaces;

use App\Enums\WorkspaceRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRoleRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(WorkspaceRole::class)->except([WorkspaceRole::Owner])],
        ];
    }

    public function role(): WorkspaceRole
    {
        return WorkspaceRole::from($this->string('role')->toString());
    }
}
