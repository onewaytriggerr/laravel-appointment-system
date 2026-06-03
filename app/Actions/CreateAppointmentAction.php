<?php

namespace App\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Enums\AppointmentStatus;

class CreateAppointmentAction
{
    // Inject the validator action to reuse validation logic
    public function __construct(
        private ValidateAppointmentAction $validator
    ) {}

    // $data: associative array with keys: branch_id, user_id, customer_id, service_id, starts_at (local datetime string)
    // Returns the created Appointment model instance
    public function execute(array $data): Appointment
    {
        // Fetch related models for validation, will throw ModelNotFoundException if any ID is not found
        $branch  = Branch::findOrFail($data['branch_id']);
        $staff   = User::findOrFail($data['user_id']);
        $service = Service::findOrFail($data['service_id']);

        // Convert input (datetime string without timezone) from branch local time to UTC for storage
        $startsAt = Carbon::parse($data['starts_at'], $branch->timezone)
                          ->setTimezone('UTC');

        // Validate all business rules — throws ValidationException on failure
        $endsAt = $this->validator->execute($startsAt, $branch, $staff, $service);

        // Create the appointment
        return Appointment::create([
            'branch_id'   => $data['branch_id'],
            'user_id'     => $data['user_id'],
            'customer_id' => $data['customer_id'],
            'service_id'  => $data['service_id'],
            'starts_at'   => $startsAt,
            'ends_at'     => $endsAt,
            'status'      => AppointmentStatus::Pending,
        ]);

    }
}