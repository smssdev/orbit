<?php

namespace Database\Seeders;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $workspace = Workspace::factory()->for($owner, 'owner')->create(['name' => 'Acme']);
        $owner->switchWorkspace($workspace);

        $workspace->addMember(User::factory()->create(['name' => 'Sara Admin']), WorkspaceRole::Admin);

        User::factory(3)->create()->each(
            fn (User $member) => $workspace->addMember($member, WorkspaceRole::Member),
        );

        WorkspaceInvitation::factory()->for($workspace)->for($owner, 'inviter')->create();
    }
}
