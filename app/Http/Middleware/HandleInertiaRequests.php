<?php

namespace App\Http\Middleware;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            // Lazy, so they reflect any workspace switch made later in the request (e.g. by EnsureCurrentWorkspace).
            'workspaces' => fn (): array => $this->workspaces($request),
            'currentWorkspace' => fn (): ?array => $this->currentWorkspace($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return list<array{id: int, name: string, role: WorkspaceRole}>
     */
    private function workspaces(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return [];
        }

        return array_values($user->workspaces()
            ->orderBy('name')
            ->get()
            ->map(fn (Workspace $workspace): array => $this->workspaceSummary($workspace))
            ->all());
    }

    /**
     * @return array{id: int, name: string, role: WorkspaceRole}|null
     */
    private function currentWorkspace(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof User || $user->current_workspace_id === null) {
            return null;
        }

        $workspace = $user->workspaces()->whereKey($user->current_workspace_id)->first();

        return $workspace instanceof Workspace ? $this->workspaceSummary($workspace) : null;
    }

    /**
     * @return array{id: int, name: string, role: WorkspaceRole}
     */
    private function workspaceSummary(Workspace $workspace): array
    {
        return [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'role' => $workspace->membership()->role,
        ];
    }
}
