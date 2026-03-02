<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// ─── Organizations & Credentials ────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    Volt::route('/organizations', 'pages.organizations.index')
        ->name('organizations.index');

    Volt::route('/organizations/create', 'pages.organizations.create')
        ->name('organizations.create');

    // Org-scoped routes — org membership enforced by middleware
    Route::middleware('org.member')->group(function () {

        Volt::route('/organizations/{organization}', 'pages.organizations.show')
            ->name('organizations.show');

        Volt::route('/organizations/{organization}/members', 'pages.organizations.members')
            ->name('organizations.members');

        Volt::route('/organizations/{organization}/settings', 'pages.organizations.settings')
            ->name('organizations.settings');

        Volt::route('/organizations/{organization}/credentials/create', 'pages.credentials.create')
            ->name('credentials.create');

        Volt::route('/organizations/{organization}/credentials/{credential}/edit', 'pages.credentials.edit')
            ->name('credentials.edit');
    });

    // ─── Super Admin Panel ───────────────────────────────────────────────────
    Route::middleware('super.admin')->prefix('admin')->name('admin.')->group(function () {

        Volt::route('/', 'pages.admin.dashboard')
            ->name('dashboard');

        Volt::route('/organizations', 'pages.admin.organizations')
            ->name('organizations');

        Volt::route('/users', 'pages.admin.users')
            ->name('users');
    });
});

// ─── Invitations — accessible to guests and authenticated users ──────────────
Volt::route('/invitations/{token}/accept', 'pages.invitations.accept')
    ->name('invitations.accept');

require __DIR__.'/auth.php';
