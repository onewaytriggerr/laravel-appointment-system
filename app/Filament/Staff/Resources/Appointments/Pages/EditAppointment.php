<?php

namespace App\Filament\Staff\Resources\Appointments\Pages;

use Illuminate\Support\Facades\Auth;
use App\Filament\Staff\Resources\Appointments\AppointmentResource;
use Filament\Resources\Pages\EditRecord;
use App\Actions\UpdateAppointmentStatusAction;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function handleRecordUpdate($record, array $data): Appointment
    {
        // Check if the appointment belongs to the currently authenticated staff member
        if ($record->user_id !== Auth::id()) {
            abort(403);
        }

        $newStatus = AppointmentStatus::from($data['status']);

        return app(UpdateAppointmentStatusAction::class)->execute(
            $record,
            $newStatus,
            $data['cancellation_reason'] ?? null
        );
    }
}
