<?php

namespace Tests\Feature\Actions;

use App\Actions\UpdateAppointmentStatusAction;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateAppointmentStatusActionTest extends TestCase
{
    use RefreshDatabase;

    private UpdateAppointmentStatusAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new UpdateAppointmentStatusAction();
    }

    // Test valid transitions and cancellation reason handling
    public function test_it_transitions_pending_to_confirmed(): void
    {
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);

        $result = $this->action->execute($appointment, AppointmentStatus::Confirmed);

        $this->assertSame(AppointmentStatus::Confirmed, $result->status);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
    }

    // Additional tests for other valid transitions
    public function test_it_transitions_confirmed_to_in_progress(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();

        $result = $this->action->execute($appointment, AppointmentStatus::InProgress);

        $this->assertSame(AppointmentStatus::InProgress, $result->status);
    }

    // Test that a valid transition from In Progress to Completed works
    public function test_it_transitions_in_progress_to_completed(): void
    {
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::InProgress]);

        $result = $this->action->execute($appointment, AppointmentStatus::Completed);

        $this->assertSame(AppointmentStatus::Completed, $result->status);
    }

    // Test that a valid transition from Confirmed to No Show works
    public function test_it_transitions_confirmed_to_no_show(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();

        $result = $this->action->execute($appointment, AppointmentStatus::NoShow);

        $this->assertSame(AppointmentStatus::NoShow, $result->status);
    }

    // Test cancellation with reason and ensure it is stored correctly
    public function test_it_transitions_pending_to_cancelled_with_reason(): void
    {
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);

        $result = $this->action->execute($appointment, AppointmentStatus::Cancelled, 'Customer requested cancellation');

        $this->assertSame(AppointmentStatus::Cancelled, $result->status);
        $this->assertSame('Customer requested cancellation', $result->cancellation_reason);
        $this->assertDatabaseHas('appointments', [
            'id'                  => $appointment->id,
            'status'              => 'cancelled',
            'cancellation_reason' => 'Customer requested cancellation',
        ]);
    }

    // Test cancellation with reason and ensure it is stored correctly
    public function test_it_transitions_confirmed_to_cancelled_with_reason(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();

        $result = $this->action->execute($appointment, AppointmentStatus::Cancelled, 'No-show risk');

        $this->assertSame(AppointmentStatus::Cancelled, $result->status);
        $this->assertSame('No-show risk', $result->cancellation_reason);
    }

    // Test that the action returns a fresh model instance
    public function test_it_returns_fresh_model_instance(): void
    {
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);

        $result = $this->action->execute($appointment, AppointmentStatus::Confirmed);

        $this->assertSame($appointment->id, $result->id);
        // fresh() returns a new object, not the same reference
        $this->assertNotSame($appointment, $result);
    }

    // Test invalid transitions and cancellation reason validation
    public function test_it_throws_when_transition_is_not_allowed(): void
    {
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Completed]);

        try {
            $this->action->execute($appointment, AppointmentStatus::Pending);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
    }

    // Test that cancelling without a reason throws a validation error
    public function test_it_throws_when_cancelling_without_reason(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();

        try {
            $this->action->execute($appointment, AppointmentStatus::Cancelled, null);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cancellation_reason', $e->errors());
        }
    }

    // Test that cancelling with an empty string reason throws a validation error
    public function test_it_throws_when_cancelling_with_empty_string_reason(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();

        try {
            $this->action->execute($appointment, AppointmentStatus::Cancelled, '');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cancellation_reason', $e->errors());
        }
    }

    // Test that non-cancellation transitions do not require a reason
    public function test_non_cancellation_transition_does_not_require_reason(): void
    {
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Pending]);

        $result = $this->action->execute($appointment, AppointmentStatus::Confirmed, null);

        $this->assertSame(AppointmentStatus::Confirmed, $result->status);
    }

    // Test that non-cancellation transitions do not overwrite existing cancellation reason
    public function test_cancellation_reason_is_not_overwritten_on_non_cancel_transition(): void
    {
        // An appointment previously cancelled then confirmed would be invalid by state machine,
        // so we test that on a Pending -> Confirmed transition the existing null reason stays null.
        $appointment = Appointment::factory()->create([
            'status'              => AppointmentStatus::Pending,
            'cancellation_reason' => null,
        ]);

        $result = $this->action->execute($appointment, AppointmentStatus::Confirmed);

        $this->assertNull($result->cancellation_reason);
    }

    public static function terminalStatusProvider(): array
    {
        return [
            'completed -> pending'   => [AppointmentStatus::Completed,  AppointmentStatus::Pending],
            'completed -> confirmed' => [AppointmentStatus::Completed,  AppointmentStatus::Confirmed],
            'completed -> cancelled' => [AppointmentStatus::Completed,  AppointmentStatus::Cancelled],
            'cancelled -> pending'   => [AppointmentStatus::Cancelled,  AppointmentStatus::Pending],
            'cancelled -> confirmed' => [AppointmentStatus::Cancelled,  AppointmentStatus::Confirmed],
            'no_show -> pending'     => [AppointmentStatus::NoShow,     AppointmentStatus::Pending],
            'no_show -> confirmed'   => [AppointmentStatus::NoShow,     AppointmentStatus::Confirmed],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('terminalStatusProvider')]
    public function test_transition_from_terminal_states_throws(
        AppointmentStatus $from,
        AppointmentStatus $to
    ): void {
        $appointment = Appointment::factory()->create(['status' => $from]);

        $this->expectException(ValidationException::class);

        $this->action->execute($appointment, $to, 'reason if needed');
    }
}
