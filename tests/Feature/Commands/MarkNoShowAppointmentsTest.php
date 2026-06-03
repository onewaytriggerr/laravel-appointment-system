<?php

namespace Tests\Feature\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkNoShowAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2025-06-02 12:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    // Tests for the appointments:mark-no-show command
    public function test_it_marks_confirmed_appointment_as_no_show_when_past_deadline(): void
    {
        // 16 minutes ago — past the 15-min threshold
        $appointment = Appointment::factory()->create([
            'status'    => AppointmentStatus::Confirmed,
            'starts_at' => Carbon::parse('2025-06-02 11:44:00', 'UTC'),
            'ends_at'   => Carbon::parse('2025-06-02 12:44:00', 'UTC'),
        ]);

        $this->artisan('appointments:mark-no-show');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'no_show']);
    }

    // Tests for edge cases around the 15-minute deadline
    public function test_it_does_not_mark_appointment_within_15_minute_window(): void
    {
        // 10 minutes ago — still within the grace window
        $appointment = Appointment::factory()->create([
            'status'    => AppointmentStatus::Confirmed,
            'starts_at' => Carbon::parse('2025-06-02 11:50:00', 'UTC'),
            'ends_at'   => Carbon::parse('2025-06-02 12:50:00', 'UTC'),
        ]);

        $this->artisan('appointments:mark-no-show');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
    }

    // Tests if the command correctly handles appointments that are exactly on the 15-minute boundary
    public function test_it_marks_appointment_at_exact_15_minute_boundary(): void
    {
        // Exactly 15 minutes ago — deadline uses <=, so this should be marked
        $appointment = Appointment::factory()->create([
            'status'    => AppointmentStatus::Confirmed,
            'starts_at' => Carbon::parse('2025-06-02 11:45:00', 'UTC'),
            'ends_at'   => Carbon::parse('2025-06-02 12:45:00', 'UTC'),
        ]);

        $this->artisan('appointments:mark-no-show');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'no_show']);
    }

    // Tests if the command does not affect appointments that are not confirmed, even if they are past the deadline
    public function test_it_does_not_affect_pending_appointments_past_deadline(): void
    {
        $appointment = Appointment::factory()->create([
            'status'    => AppointmentStatus::Pending,
            'starts_at' => Carbon::parse('2025-06-02 11:00:00', 'UTC'),
            'ends_at'   => Carbon::parse('2025-06-02 12:00:00', 'UTC'),
        ]);

        $this->artisan('appointments:mark-no-show');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'pending']);
    }

    // Tests if the command does not affect appointments that are cancelled, even if they are past the deadline
    public function test_it_does_not_affect_cancelled_appointments_past_deadline(): void
    {
        $appointment = Appointment::factory()->create([
            'status'              => AppointmentStatus::Cancelled,
            'cancellation_reason' => 'Test',
            'starts_at'           => Carbon::parse('2025-06-02 11:00:00', 'UTC'),
            'ends_at'             => Carbon::parse('2025-06-02 12:00:00', 'UTC'),
        ]);

        $this->artisan('appointments:mark-no-show');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelled']);
    }

    // Tests if the command correctly processes multiple appointments in one run
    public function test_it_processes_multiple_qualifying_appointments(): void
    {
        $appointments = Appointment::factory()->count(3)->create([
            'status'    => AppointmentStatus::Confirmed,
            'starts_at' => Carbon::parse('2025-06-02 11:00:00', 'UTC'),
            'ends_at'   => Carbon::parse('2025-06-02 12:00:00', 'UTC'),
        ]);

        $this->artisan('appointments:mark-no-show');

        foreach ($appointments as $appointment) {
            $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'no_show']);
        }
    }

    // Tests if the command only marks appointments that are confirmed and past the deadline, ignoring others
    public function test_it_only_marks_qualifying_confirmed_appointments(): void
    {
        // 2 past-deadline confirmed → should be marked
        $pastConfirmed = Appointment::factory()->count(2)->create([
            'status'    => AppointmentStatus::Confirmed,
            'starts_at' => Carbon::parse('2025-06-02 11:00:00', 'UTC'),
            'ends_at'   => Carbon::parse('2025-06-02 12:00:00', 'UTC'),
        ]);

        // 1 recent confirmed → should NOT be marked
        $recentConfirmed = Appointment::factory()->create([
            'status'    => AppointmentStatus::Confirmed,
            'starts_at' => Carbon::parse('2025-06-02 11:55:00', 'UTC'),
            'ends_at'   => Carbon::parse('2025-06-02 12:55:00', 'UTC'),
        ]);

        // 1 past pending → should NOT be marked
        $pastPending = Appointment::factory()->create([
            'status'    => AppointmentStatus::Pending,
            'starts_at' => Carbon::parse('2025-06-02 11:00:00', 'UTC'),
            'ends_at'   => Carbon::parse('2025-06-02 12:00:00', 'UTC'),
        ]);

        $this->artisan('appointments:mark-no-show');

        foreach ($pastConfirmed as $appointment) {
            $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'no_show']);
        }

        $this->assertDatabaseHas('appointments', ['id' => $recentConfirmed->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('appointments', ['id' => $pastPending->id, 'status' => 'pending']);
    }

    // Tests if the command outputs the expected messages for each marked appointment
    public function test_it_outputs_line_for_each_marked_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'status'    => AppointmentStatus::Confirmed,
            'starts_at' => Carbon::parse('2025-06-02 11:00:00', 'UTC'),
            'ends_at'   => Carbon::parse('2025-06-02 12:00:00', 'UTC'),
        ]);

        $this->artisan('appointments:mark-no-show')
            ->expectsOutputToContain("Appointment #{$appointment->id} marked as no-show.");
    }
}
