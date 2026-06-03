<?php

namespace Tests\Feature\Actions;

use App\Actions\CreateAppointmentAction;
use App\Enums\AppointmentStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateAppointmentActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateAppointmentAction $action;
    private Branch $branch;
    private User $staff;
    private Service $service;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2025-06-02 02:00:00', 'UTC'));

        $this->action   = app(CreateAppointmentAction::class);
        $this->branch   = Branch::factory()->utcTimezone()->create();
        $this->service  = Service::factory()->create(['duration_minutes' => 60]);
        $this->staff    = User::factory()->staff($this->branch->id)->create();
        $this->customer = Customer::factory()->create();
    }

    // Reset Carbon's test time after each test to avoid side effects.
    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    // Test that a valid appointment is created successfully with pending status
    public function test_it_creates_appointment_with_status_pending(): void
    {
        $appointment = $this->action->execute($this->validData());

        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
    }

    // Test that the appointment is saved in the database with correct details
    public function test_it_persists_appointment_to_database(): void
    {
        $this->action->execute($this->validData());

        $this->assertDatabaseHas('appointments', [
            'branch_id'  => $this->branch->id,
            'user_id'    => $this->staff->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'status'     => 'pending',
        ]);
    }

    // Test that the starts_at time is stored in UTC regardless of input timezone
    public function test_it_converts_local_time_to_utc_for_storage(): void
    {
        // Branch uses UTC, so local time = UTC. Pass "10:00 local" → expect "10:00 UTC" stored.
        $data        = $this->validData(['starts_at' => '2025-06-02 10:00:00']);
        $appointment = $this->action->execute($data);

        $this->assertTrue(
            $appointment->starts_at->equalTo(Carbon::parse('2025-06-02 10:00:00', 'UTC'))
        );
    }

    // Test that if the branch is in a non-UTC timezone, the input local time is correctly converted to UTC for storage
    public function test_it_converts_myt_local_time_to_utc_for_storage(): void
    {
        // Branch is UTC+8 (Asia/Kuala_Lumpur). "10:00 local" = "02:00 UTC".
        $branch  = Branch::factory()->create(['timezone' => 'Asia/Kuala_Lumpur', 'opening_time' => '09:00:00', 'closing_time' => '18:00:00']);
        $staff   = User::factory()->staff($branch->id)->create();
        $service = Service::factory()->create(['duration_minutes' => 60]);

        $data = $this->validData([
            'branch_id'  => $branch->id,
            'user_id'    => $staff->id,
            'service_id' => $service->id,
            'starts_at'  => '2025-06-02 10:00:00', // 10:00 MYT → 02:00 UTC
        ]);

        $appointment = $this->action->execute($data);

        $this->assertTrue(
            $appointment->starts_at->equalTo(Carbon::parse('2025-06-02 02:00:00', 'UTC'))
        );
    }

    // Test that the ends_at time is automatically calculated based on the service duration and stored in UTC
    public function test_it_sets_ends_at_based_on_service_duration(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 90]);

        $data        = $this->validData(['service_id' => $service->id, 'starts_at' => '2025-06-02 10:00:00']);
        $appointment = $this->action->execute($data);

        $this->assertTrue(
            $appointment->ends_at->equalTo(Carbon::parse('2025-06-02 11:30:00', 'UTC'))
        );
    }

    // Test that the action throws a ModelNotFoundException for an invalid branch ID
    public function test_it_throws_model_not_found_for_invalid_branch_id(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->action->execute($this->validData(['branch_id' => 9999]));
    }

    // Test that the action throws a ModelNotFoundException for an invalid staff ID
    public function test_it_throws_model_not_found_for_invalid_staff_id(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->action->execute($this->validData(['user_id' => 9999]));
    }

    // Test that the action throws a ModelNotFoundException for an invalid customer ID
    public function test_it_throws_model_not_found_for_invalid_service_id(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->action->execute($this->validData(['service_id' => 9999]));
    }

    // Test that the action throws a ValidationException if the staff does not belong to the specified branch
    public function test_it_throws_when_staff_does_not_belong_to_branch(): void
    {
        $otherBranch = Branch::factory()->utcTimezone()->create();

        $this->expectException(ValidationException::class);

        $this->action->execute($this->validData(['branch_id' => $otherBranch->id]));
    }

    // Helper method to generate valid appointment data with optional overrides
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'branch_id'   => $this->branch->id,
            'user_id'     => $this->staff->id,
            'customer_id' => $this->customer->id,
            'service_id'  => $this->service->id,
            'starts_at'   => '2025-06-02 10:00:00',
        ], $overrides);
    }
}
