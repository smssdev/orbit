<?php

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
});

// Accepting only needs a signed-in account; following the emailed link already proves the address.
Route::middleware('auth')->group(function (): void {
    Route::get('invitations/{token}', [AcceptInvitationController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{token}', [AcceptInvitationController::class, 'store'])->name('invitations.accept');
});
