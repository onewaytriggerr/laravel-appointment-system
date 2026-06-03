<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $startsAt = now('UTC')->setTime(10, 0, 0);

        return [
            'branch_id'           => Branch::factory()->utcTimezone(),
            'user_id'             => User::factory()->state(['role' => 'staff']),
            'customer_id'         => Customer::factory(),
            'service_id'          => Service::factory(),
            'starts_at'           => $startsAt,
            'ends_at'             => $startsAt->copy()->addHour(),
            'status'              => AppointmentStatus::Pending,
            'cancellation_reason' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => AppointmentStatus::Confirmed]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status'              => AppointmentStatus::Cancelled,
            'cancellation_reason' => 'Test cancellation',
        ]);
    }

    public function noShow(): static
    {
        return $this->state(['status' => AppointmentStatus::NoShow]);
    }

    public function pastConfirmed(int $minutesAgo = 30): static
    {
        $start = now('UTC')->subMinutes($minutesAgo);
        return $this->state([
            'status'    => AppointmentStatus::Confirmed,
            'starts_at' => $start,
            'ends_at'   => $start->copy()->addHour(),
        ]);
    }
}
