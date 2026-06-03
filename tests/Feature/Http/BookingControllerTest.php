<?php

namespace Tests\Feature\Http;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Service $service;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2025-06-02 02:00:00', 'UTC'));

        $this->branch  = Branch::factory()->utcTimezone()->create();
        $this->service = Service::factory()->create(['duration_minutes' => 60]);
        $this->staff   = User::factory()->staff($this->branch->id)->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    // --- GET / ---

    public function test_show_returns_200_with_branches_and_services(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('branches');
        $response->assertViewHas('services');
    }

    // --- POST /booking ---

    public function test_store_creates_appointment_and_redirects_to_success(): void
    {
        $response = $this->post('/booking', $this->validPayload());

        $response->assertRedirect(route('booking.success'));
        $this->assertNotNull(session('appointment_id'));
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_store_creates_new_customer_if_not_exists(): void
    {
        $this->post('/booking', $this->validPayload([
            'name'  => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+60123456789',
        ]));

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', ['name' => 'John Doe', 'email' => 'john@example.com']);
    }

    public function test_store_reuses_existing_customer_by_email_and_phone(): void
    {
        Customer::factory()->create(['email' => 'john@example.com', 'phone' => '+60123456789']);

        $this->post('/booking', $this->validPayload([
            'name'  => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+60123456789',
        ]));

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->post('/booking', []);

        $response->assertSessionHasErrors(['name', 'branch_id', 'service_id', 'user_id', 'starts_at']);
    }

    public function test_store_validates_branch_exists(): void
    {
        $response = $this->post('/booking', $this->validPayload(['branch_id' => 9999]));

        $response->assertSessionHasErrors(['branch_id']);
    }

    public function test_store_validates_staff_exists(): void
    {
        $response = $this->post('/booking', $this->validPayload(['user_id' => 9999]));

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_store_returns_validation_error_when_appointment_is_outside_operating_hours(): void
    {
        // Branch opens 09:00 UTC; submitting 08:00 should fail business rule validation
        $response = $this->post('/booking', $this->validPayload(['starts_at' => '2025-06-02 08:00:00']));

        $response->assertSessionHasErrors(['starts_at']);
    }

    // --- GET /booking/success ---

    public function test_success_shows_appointment_from_session(): void
    {
        $this->post('/booking', $this->validPayload());
        $appointmentId = session('appointment_id');

        $response = $this->withSession(['appointment_id' => $appointmentId])
            ->get(route('booking.success'));

        $response->assertStatus(200);
        $response->assertViewHas('appointment', fn ($a) => $a->id === $appointmentId);
    }

    public function test_success_shows_null_appointment_without_session(): void
    {
        $response = $this->get(route('booking.success'));

        $response->assertStatus(200);
        $response->assertViewHas('appointment', null);
    }

    // --- GET /branches/{branch}/staff ---

    public function test_branches_staff_endpoint_returns_only_staff_role_users(): void
    {
        $admin = User::factory()->admin()->create();
        // Manually assign admin to branch to confirm they're excluded even if in the branch
        $admin->update(['branch_id' => $this->branch->id]);

        $response = $this->getJson(route('branches.staff', $this->branch));

        $response->assertStatus(200);
        $response->assertJsonCount(1); // only $this->staff, not the admin
        $response->assertJsonFragment(['id' => $this->staff->id]);
        $response->assertJsonMissing(['id' => $admin->id]);
    }

    public function test_branches_staff_endpoint_returns_404_for_unknown_branch(): void
    {
        $response = $this->getJson('/branches/9999/staff');

        $response->assertStatus(404);
    }

    // --- Helper ---

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'       => 'Test Customer',
            'email'      => 'test@example.com',
            'phone'      => '+60123456789',
            'branch_id'  => $this->branch->id,
            'service_id' => $this->service->id,
            'user_id'    => $this->staff->id,
            'starts_at'  => '2025-06-02 10:00:00',
        ], $overrides);
    }
}
