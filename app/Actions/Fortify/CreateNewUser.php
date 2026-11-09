<?php

namespace App\Actions\Fortify;

use App\Actions\Workspaces\CreateWorkspace;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private readonly CreateWorkspace $createWorkspace) {}

    /**
     * Validate and create a newly registered user, with a personal workspace to start in.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            // Keep the language they signed up in, so later emails (verification, resets) use it too.
            $user->forceFill(['locale' => app()->getLocale()])->save();

            $firstName = Str::before(trim($user->name), ' ');
            $this->createWorkspace->handle($user, __(":name's workspace", ['name' => $firstName]));

            return $user;
        });
    }
}
