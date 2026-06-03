<?php

namespace App\Actions;

use Illuminate\Validation\ValidationException;
use App\Models\Appointment;
use App\Enums\AppointmentStatus;

class UpdateAppointmentStatusAction
{
    public function execute(
        Appointment $appointment,
        AppointmentStatus $newStatus,
        ?string $cancellationReason = null
    ): Appointment {
        // Validate the transition is allowed
        if (! $appointment->status->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Cannot transition from "%s" to "%s".',
                    $appointment->status->label(),
                    $newStatus->label()
                ),
            ]);
        }

        // Cancellation reason is required when cancelling
        if ($newStatus === AppointmentStatus::Cancelled && empty($cancellationReason)) {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'A cancellation reason is required.',
            ]);
        }

        $appointment->update([
            'status'              => $newStatus,
            'cancellation_reason' => $newStatus === AppointmentStatus::Cancelled ? $cancellationReason : $appointment->cancellation_reason,
        ]);

        return $appointment->fresh();
    }
}