<?php

namespace App\Actions;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\User;
use App\Models\Service;
use App\Enums\AppointmentStatus;

class ValidateAppointmentAction
{
    /**
     * Validates the proposed appointment time against business rules:
     * - Staff belongs to the branch
     * - Appointment falls within branch operating hours
     * - No overlapping appointments for the staff
     * - (Optional) Excludes a specific appointment ID (useful when updating an existing appointment)
     * Returns the calculated end time based on service duration if validation passes.
     * Throws ValidationException with appropriate messages if any validation fails.
     */
    public function execute(
        Carbon $startsAt,
        Branch $branch,
        User $staff,
        Service $service,
        ?int $excludeAppointmentId = null
    ): Carbon {
        // 1. Calculate end time from service duration
        $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes);

        // 2. Check if staff belongs to this branch
        if ($staff->branch_id !== $branch->id) {
            throw ValidationException::withMessages([
                'user_id' => 'This staff member does not belong to the selected branch.',
            ]);
        }

        // 3. Validate operating hours
        $this->validateOperatingHours($startsAt, $endsAt, $branch);

        // 4. Validate no overlap with existing appointments
        $this->validateNoOverlap($startsAt, $endsAt, $staff, $excludeAppointmentId);

        return $endsAt;
    }

    private function validateOperatingHours(
        Carbon $startsAt,
        Carbon $endsAt,
        Branch $branch
    ): void {
        // Convert UTC times to branch local timezone for comparison
        $branchTimeZone = $branch->timezone;
        $localStart     = $startsAt->copy()->setTimezone($branchTimeZone);
        $localEnd       = $endsAt->copy()->setTimezone($branchTimeZone);

        // Calculate opening and closing times for the appointment date in branch local time
        $openingTime = Carbon::parse($localStart->toDateString() . ' ' . $branch->opening_time, $branchTimeZone);
        $closingTime = Carbon::parse($localStart->toDateString() . ' ' . $branch->closing_time, $branchTimeZone);

        // Check if appointment falls entirely within operating hours
        if ($localStart->lt($openingTime) || $localEnd->gt($closingTime)) {
            throw ValidationException::withMessages([
                'starts_at' => sprintf(
                    'The appointment must fall entirely within branch operating hours (%s - %s %s).',
                    $branch->opening_time,
                    $branch->closing_time,
                    $branchTimeZone
                ),
            ]);
        }
    }

    private function validateNoOverlap(
        Carbon $startsAt,
        Carbon $endsAt,
        User $staff,
        ?int $excludeId // ID of appointment to exclude from check (for edits)
    ): void {
        // 1. Find blocking statuses from AppointmentStatus enum
        $blockingStatuses = [];
        foreach (AppointmentStatus::cases() as $status) {
            if ($status->blocksAvailability()) {
                $blockingStatuses[] = $status->value;
            }
        }

        // 2. Database query to check for overlapping appointments for the staff. Check if there are any appointments that:
        //    - Belong to the same staff member
        //    - Have a status that blocks availability
        //    - Are within the proposed start and end times
        $query = Appointment::query()
            ->where('user_id', $staff->id)
            ->whereIn('status', $blockingStatuses)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);

        // 3. If an excludeId is provided (for updates), exclude that appointment from the check
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        // 4. Check if any record matches
        $overlapExists = $query->exists();

        // 5. Throw error if overlap exists
        if ($overlapExists) {
            throw ValidationException::withMessages([
                'starts_at' => 'This staff member already has an appointment during this time.',
            ]);
        }
    }
}