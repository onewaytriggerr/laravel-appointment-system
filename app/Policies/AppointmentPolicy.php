<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
// use Illuminate\Auth\Access\Response;

class AppointmentPolicy
{
    // Only admin can view the list of appointments
    public function viewAny(User $user): bool
    {
        return $user->isAdmin(); 
    }

    // Determine if the user can view the appointment
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isAdmin()) return true;

        return $appointment->user_id == $user->id; // Staff can only view their own appointments
    }

    // Only admins can create appointments
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    // Determine if the user can update the appointment
    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->isAdmin()) return true;

        return $appointment->user_id == $user->id; // Staff can only update their own appointments
    }

    // Only admins can delete appointments
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin();
    }
}
