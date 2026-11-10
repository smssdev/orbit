<?php

use App\Http\Controllers\Board\ColumnController;
use App\Http\Controllers\Board\ColumnPositionController;
use App\Http\Controllers\Board\LabelController;
use App\Http\Controllers\Board\TaskController;
use App\Http\Controllers\Board\TaskPositionController;
use App\Http\Controllers\Projects\ProjectArchiveController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Workspaces\AcceptInvitationController;
use App\Http\Controllers\Workspaces\CurrentWorkspaceController;
use App\Http\Controllers\Workspaces\InvitationController;
use App\Http\Controllers\Workspaces\MemberController;
use App\Http\Controllers\Workspaces\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('workspaces/create', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::put('current-workspace', [CurrentWorkspaceController::class, 'update'])->name('current-workspace.update');

    // Settings for the user's current workspace, so URLs never expose another workspace's ID.
    Route::middleware('workspace')->prefix('workspace')->name('workspace.')->group(function (): void {
        Route::get('settings', [WorkspaceController::class, 'edit'])->name('edit');
        Route::patch('settings', [WorkspaceController::class, 'update'])->name('update');

        Route::get('members', [MemberController::class, 'index'])->name('members.index');
        Route::patch('members/{member}', [MemberController::class, 'update'])->name('members.update');
        Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

        Route::post('invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
    });

    // Projects of the current workspace (a project from another one answers 404).
    Route::middleware('workspace')->group(function (): void {
        Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::patch('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('projects/{project}/archive', [ProjectArchiveController::class, 'store'])->name('projects.archive');
        Route::delete('projects/{project}/archive', [ProjectArchiveController::class, 'destroy'])->name('projects.restore');

        // The project's board. Columns and cards are looked up within the project, so others answer 404.
        Route::scopeBindings()->prefix('projects/{project}')->name('projects.')->group(function (): void {
            Route::post('columns', [ColumnController::class, 'store'])->name('columns.store');
            Route::patch('columns/{column}', [ColumnController::class, 'update'])->name('columns.update');
            Route::delete('columns/{column}', [ColumnController::class, 'destroy'])->name('columns.destroy');
            Route::put('columns/{column}/position', [ColumnPositionController::class, 'update'])->name('columns.position');

            Route::post('columns/{column}/tasks', [TaskController::class, 'store'])->name('tasks.store');
            Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
            Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
            Route::put('tasks/{task}/position', [TaskPositionController::class, 'update'])->name('tasks.position');

            Route::post('labels', [LabelController::class, 'store'])->name('labels.store');
            Route::delete('labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');
        });
    });
});

// Accepting only needs a signed-in account; following the emailed link already proves the address.
Route::middleware('auth')->group(function (): void {
    Route::get('invitations/{token}', [AcceptInvitationController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{token}', [AcceptInvitationController::class, 'store'])->name('invitations.accept');
});
