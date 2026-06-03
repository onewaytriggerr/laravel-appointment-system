<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\Branch;
use App\Models\Service;
use App\Models\User;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Model;
use App\Enums\AppointmentStatus;
use App\Actions\ValidateAppointmentAction;
use App\Actions\UpdateAppointmentStatusAction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Appointment // $record refers to the appointment being edited
    {
        return DB::transaction(function () use ($record, $data) {
            $branch  = Branch::findOrFail($data['branch_id']);
            $staff   = User::findOrFail($data['user_id']);
            $service = Service::findOrFail($data['service_id']);

            $startsAt = Carbon::parse($data['starts_at'], $branch->timezone)->setTimezone('UTC');

            $endsAt = app(ValidateAppointmentAction::class)->execute(
                $startsAt, $branch, $staff, $service,
                excludeAppointmentId: $record->id  //exclude current appointment from validation to allow updating without changing time
            );

            // If status is being changed, use UpdateAppointmentStatusAction to validate the transition and handle cancellation reason if needed
            $newStatus = AppointmentStatus::from($data['status']);
            if ($newStatus !== $record->status) {
                app(UpdateAppointmentStatusAction::class)->execute(
                    $record,
                    $newStatus,
                    $data['cancellation_reason'] ?? null
                );
            }

            $record->update([
                'branch_id'   => $data['branch_id'],
                'user_id'     => $data['user_id'],
                'customer_id' => $data['customer_id'],
                'service_id'  => $data['service_id'],
                'starts_at'   => $startsAt,
                'ends_at'     => $endsAt,
            ]);

            return $record->fresh();
        });
    }
}
