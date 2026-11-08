<?php

namespace App\Http\Requests\Workspaces;

use App\Enums\WorkspaceRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InviteMemberRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', Rule::enum(WorkspaceRole::class)->except([WorkspaceRole::Owner])],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('email') || ! $this->inviteeIsAlreadyAMember()) {
                    return;
                }

                $validator->errors()->add('email', __('This person is already a member of the workspace.'));
            },
        ];
    }

    public function role(): WorkspaceRole
    {
        return WorkspaceRole::from($this->string('role')->toString());
    }

    private function inviteeIsAlreadyAMember(): bool
    {
        $workspace = $this->user()?->currentWorkspace;

        return $workspace !== null && $workspace->members()
            ->whereRaw('lower(email) = ?', [Str::lower($this->string('email')->toString())])
            ->exists();
    }
}
