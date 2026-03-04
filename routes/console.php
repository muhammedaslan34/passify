<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('super-admin:grant {email : The user email to grant super admin access}', function (string $email): void {
    $user = User::where('email', $email)->first();
    if (! $user) {
        $this->error("User with email [{$email}] not found.");
        return;
    }
    $user->update(['is_super_admin' => true]);
    $this->info("Super admin access granted to {$user->name} ({$email}).");
})->purpose('Grant super admin access to a user by email');
