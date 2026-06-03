<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Appointment;
use App\Enums\AppointmentStatus;

#[Signature('appointments:mark-no-show')]
#[Description('Mark confirmed appointments as no-show if 15 min past start time')]
class MarkNoShowAppointments extends Command
{
    public function handle(): void
    {
        $deadline = Carbon::now('UTC')->subMinutes(15);

        Appointment::query()
            ->where('status', AppointmentStatus::Confirmed)
            ->where('starts_at', '<=', $deadline)
            ->each(function (Appointment $appointment) {
                $appointment->update(['status' => AppointmentStatus::NoShow]);
                $this->line("Appointment #{$appointment->id} marked as no-show.");
            });
    }
}
