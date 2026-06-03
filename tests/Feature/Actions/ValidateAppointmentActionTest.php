<?php

namespace Tests\Feature\Actions;

use App\Actions\ValidateAppointmentAction;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ValidateAppointmentActionTest extends TestCase
{
    use RefreshDatabase;

    private ValidateAppointmentAction $action;
    private Branch $branch;
    private User $staff;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2025-06-02 10:00:00', 'UTC'));

        $this->action  = new ValidateAppointmentAction();
        $this->branch  = Branch::factory()->utcTimezone()->create();
        $this->service = Service::factory()->create(['duration_minutes' => 60]);
        $this->staff   = User::factory()->staff($this->branch->id)->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    // Test that valid appointment returns correct ends_at time based on service duration
    public function test_it_returns_ends_at_on_valid_appointment(): void
    {
        $startsAt = Carbon::parse('2025-06-02 10:00:00', 'UTC');

        $endsAt = $this->action->execute($startsAt, $this->branch, $this->staff, $this->service);

        $this->assertInstanceOf(Carbon::class, $endsAt);
        $this->assertTrue($endsAt->equalTo(Carbon::parse('2025-06-02 11:00:00', 'UTC')));
    }

    // Test that it throws validation error when staff belongs to a different branch than the appointment's branch
    public function test_it_throws_when_staff_belongs_to_different_branch(): void
    {
        $otherBranch = Branch::factory()->utcTimezone()->create();
        $startsAt    = Carbon::parse('2025-06-02 10:00:00', 'UTC');

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $otherBranch, $this->staff, $this->service),
            'user_id'
        );
    }

    // Test that it throws validation error when appointment starts before opening time of the branch
    public function test_it_throws_when_appointment_starts_before_opening_time(): void
    {
        // Branch opens 09:00 UTC; 08:00 is before opening
        $startsAt = Carbon::parse('2025-06-02 08:00:00', 'UTC');

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $this->branch, $this->staff, $this->service),
            'starts_at'
        );
    }

    // Test that it throws validation error when appointment ends after closing time of the branch
    public function test_it_throws_when_appointment_ends_after_closing_time(): void
    {
        // Branch closes 18:00 UTC; 60-min service starting at 17:30 ends at 18:30
        $startsAt = Carbon::parse('2025-06-02 17:30:00', 'UTC');

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $this->branch, $this->staff, $this->service),
            'starts_at'
        );
    }

    // Test that it throws validation error when appointment starts exactly at closing time (even if it would end after closing)
    public function test_it_throws_when_appointment_starts_exactly_at_closing_time(): void
    {
        // Starts at 18:00, 60-min service → ends at 19:00, past closing
        $startsAt = Carbon::parse('2025-06-02 18:00:00', 'UTC');

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $this->branch, $this->staff, $this->service),
            'starts_at'
        );
    }

    // Test that it allows appointment that ends exactly at closing time
    public function test_it_accepts_appointment_ending_exactly_at_closing_time(): void
    {
        // 60-min service starting at 17:00 ends exactly at 18:00 (closing) — should pass
        $startsAt = Carbon::parse('2025-06-02 17:00:00', 'UTC');

        $endsAt = $this->action->execute($startsAt, $this->branch, $this->staff, $this->service);

        $this->assertTrue($endsAt->equalTo(Carbon::parse('2025-06-02 18:00:00', 'UTC')));
    }

    // Test that it throws validation error when a pending appointment overlaps with the new appointment
    public function test_it_throws_when_pending_appointment_overlaps(): void
    {
        $this->createAppointment('10:00', '11:00', AppointmentStatus::Pending);

        $startsAt = Carbon::parse('2025-06-02 10:30:00', 'UTC');

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $this->branch, $this->staff, $this->service),
            'starts_at'
        );
    }

    // Test that it throws validation error when a confirmed appointment overlaps with the new appointment
    public function test_it_throws_when_confirmed_appointment_overlaps(): void
    {
        $this->createAppointment('10:00', '11:00', AppointmentStatus::Confirmed);

        $startsAt = Carbon::parse('2025-06-02 10:30:00', 'UTC');

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $this->branch, $this->staff, $this->service),
            'starts_at'
        );
    }

    // Test that it throws validation error when an in-progress appointment overlaps with the new appointment
    public function test_it_throws_when_in_progress_appointment_overlaps(): void
    {
        $this->createAppointment('10:00', '11:00', AppointmentStatus::InProgress);

        $startsAt = Carbon::parse('2025-06-02 10:30:00', 'UTC');

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $this->branch, $this->staff, $this->service),
            'starts_at'
        );
    }

    // Test that it throws validation error when a completed appointment overlaps with the new appointment
    public function test_it_throws_when_completed_appointment_overlaps(): void
    {
        $this->createAppointment('10:00', '11:00', AppointmentStatus::Completed);

        $startsAt = Carbon::parse('2025-06-02 10:30:00', 'UTC');

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $this->branch, $this->staff, $this->service),
            'starts_at'
        );
    }

    // Test that a cancelled appointment does not block the slot for new appointments
    public function test_cancelled_appointment_does_not_block_slot(): void
    {
        $this->createAppointment('10:00', '11:00', AppointmentStatus::Cancelled);

        $startsAt = Carbon::parse('2025-06-02 10:00:00', 'UTC');

        $endsAt = $this->action->execute($startsAt, $this->branch, $this->staff, $this->service);

        $this->assertInstanceOf(Carbon::class, $endsAt);
    }

    // Test that a no-show appointment does not block the slot for new appointments
    public function test_no_show_appointment_does_not_block_slot(): void
    {
        $this->createAppointment('10:00', '11:00', AppointmentStatus::NoShow);

        $startsAt = Carbon::parse('2025-06-02 10:00:00', 'UTC');

        $endsAt = $this->action->execute($startsAt, $this->branch, $this->staff, $this->service);

        $this->assertInstanceOf(Carbon::class, $endsAt);
    }

    // Test it throws validation error when a new appointment partially overlaps at the start of an existing appointment
    public function test_it_detects_partial_overlap_at_start(): void
    {
        // Existing 10:00–11:00; new 09:30–10:30 overlaps at start
        $this->createAppointment('10:00', '11:00', AppointmentStatus::Confirmed);

        $startsAt = Carbon::parse('2025-06-02 09:30:00', 'UTC');
        $service  = Service::factory()->create(['duration_minutes' => 60]);

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $this->branch, $this->staff, $service),
            'starts_at'
        );
    }

    // Test it throws validation error when a new appointment partially overlaps at the end of an existing appointment
    public function test_it_detects_partial_overlap_at_end(): void
    {
        // Existing 10:00–11:00; new 10:30–11:30 overlaps at end
        $this->createAppointment('10:00', '11:00', AppointmentStatus::Confirmed);

        $startsAt = Carbon::parse('2025-06-02 10:30:00', 'UTC');

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $this->branch, $this->staff, $this->service),
            'starts_at'
        );
    }

    // Test it throws validation error when a new appointment is fully contained within the time range of an existing appointment
    public function test_it_detects_fully_contained_overlap(): void
    {
        // Existing 09:00–13:00; new 10:00–11:00 is fully inside
        $this->createAppointment('09:00', '13:00', AppointmentStatus::Confirmed);

        $startsAt = Carbon::parse('2025-06-02 10:00:00', 'UTC');

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $this->branch, $this->staff, $this->service),
            'starts_at'
        );
    }

    // Test that adjacent appointments that touch at the boundary do not count as overlapping
    public function test_adjacent_appointments_do_not_overlap(): void
    {
        // Existing ends at 10:00; new starts at 10:00 — touching boundary, not overlapping
        // Query: ends_at > starts_at → 10:00 > 10:00 is false → no overlap
        $this->createAppointment('09:00', '10:00', AppointmentStatus::Confirmed);

        $startsAt = Carbon::parse('2025-06-02 10:00:00', 'UTC');

        $endsAt = $this->action->execute($startsAt, $this->branch, $this->staff, $this->service);

        $this->assertInstanceOf(Carbon::class, $endsAt);
    }

    // Test that when editing an existing appointment, excluding its own ID allows it to be validated against other appointments without being blocked by itself
    public function test_exclude_appointment_id_allows_editing_own_appointment(): void
    {
        $existing = $this->createAppointment('10:00', '11:00', AppointmentStatus::Pending);

        $startsAt = Carbon::parse('2025-06-02 10:00:00', 'UTC');

        $endsAt = $this->action->execute($startsAt, $this->branch, $this->staff, $this->service, $existing->id);

        $this->assertInstanceOf(Carbon::class, $endsAt);
    }

    // Test that when excluding an appointment ID, it still detects overlaps with other appointments and does not allow double-booking
    public function test_exclude_appointment_id_still_catches_other_overlapping_appointments(): void
    {
        $apptA = $this->createAppointment('10:00', '11:00', AppointmentStatus::Pending);
        $apptB = $this->createAppointment('10:30', '11:30', AppointmentStatus::Pending);

        // Excluding B from check — but A still overlaps with 10:30–11:30
        $startsAt = Carbon::parse('2025-06-02 10:30:00', 'UTC');

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $this->branch, $this->staff, $this->service, $apptB->id),
            'starts_at'
        );
    }

    // Test that it validates operating hours using the branch's local timezone, not just UTC
    public function test_it_validates_operating_hours_using_branch_local_timezone(): void
    {
        // Branch in Asia/Kuala_Lumpur (UTC+8), opens 09:00 MYT
        // 09:00 MYT = 01:00 UTC → should pass
        $branch  = Branch::factory()->create(['timezone' => 'Asia/Kuala_Lumpur', 'opening_time' => '09:00:00', 'closing_time' => '18:00:00']);
        $staff   = User::factory()->staff($branch->id)->create();
        $service = Service::factory()->create(['duration_minutes' => 30]);

        $startsAt = Carbon::parse('2025-06-02 01:00:00', 'UTC'); // 09:00 MYT

        $endsAt = $this->action->execute($startsAt, $branch, $staff, $service);

        $this->assertInstanceOf(Carbon::class, $endsAt);
    }

    // Test that it rejects appointments that start before opening time when considering the branch's local timezone
    public function test_it_rejects_appointment_before_opening_in_branch_timezone(): void
    {
        // Branch in Asia/Kuala_Lumpur, opens 09:00 MYT
        // 08:59 MYT = 00:59 UTC → should fail
        $branch  = Branch::factory()->create(['timezone' => 'Asia/Kuala_Lumpur', 'opening_time' => '09:00:00', 'closing_time' => '18:00:00']);
        $staff   = User::factory()->staff($branch->id)->create();
        $service = Service::factory()->create(['duration_minutes' => 30]);

        $startsAt = Carbon::parse('2025-06-02 00:59:00', 'UTC'); // 08:59 MYT

        $this->assertValidationError(
            fn () => $this->action->execute($startsAt, $branch, $staff, $service),
            'starts_at'
        );
    }

    // --- Helpers ---

    private function createAppointment(string $startTime, string $endTime, AppointmentStatus $status): Appointment
    {
        return Appointment::factory()->create([
            'branch_id'  => $this->branch->id,
            'user_id'    => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at'  => Carbon::parse("2025-06-02 {$startTime}:00", 'UTC'),
            'ends_at'    => Carbon::parse("2025-06-02 {$endTime}:00", 'UTC'),
            'status'     => $status,
        ]);
    }

    private function assertValidationError(callable $callback, string $field): void
    {
        try {
            $callback();
            $this->fail("Expected ValidationException for field '{$field}' but none was thrown.");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors(), "Expected validation error on '{$field}'");
        }
    }
}
