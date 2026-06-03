<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Policies\AppointmentPolicy;
use App\Models\Appointment;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the AppointmentPolicy for the Appointment model
        Gate::policy(Appointment::class, AppointmentPolicy::class);

        // Admin gate - global gate to check if user is admin
        Gate::define('admin', function (User $user) { return $user->isAdmin(); });
    }
}
