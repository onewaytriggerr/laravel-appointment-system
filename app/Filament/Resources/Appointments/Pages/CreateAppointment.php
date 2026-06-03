<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Actions\CreateAppointmentAction;
use App\Models\Appointment;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function handleRecordCreation(array $data): Appointment
    {
        return app(CreateAppointmentAction::class)->execute($data); // Use the action to create the appointment with validation and business logic
    }
}
