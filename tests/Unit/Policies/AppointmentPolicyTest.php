<?php

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use PHPUnit\Framework\TestCase;

class AppointmentPolicyTest extends TestCase
{
    private AppointmentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AppointmentPolicy();
    }

    // --- Admin capabilities ---

    public function test_admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeAdmin()));
    }

    public function test_admin_can_create(): void
    {
        $this->assertTrue($this->policy->create($this->makeAdmin()));
    }

    public function test_admin_can_view_any_appointment(): void
    {
        $admin       = $this->makeAdmin(1);
        $appointment = $this->makeAppointment(user_id: 99);

        $this->assertTrue($this->policy->view($admin, $appointment));
    }

    public function test_admin_can_update_any_appointment(): void
    {
        $admin       = $this->makeAdmin(1);
        $appointment = $this->makeAppointment(user_id: 99);

        $this->assertTrue($this->policy->update($admin, $appointment));
    }

    public function test_admin_can_delete(): void
    {
        $admin       = $this->makeAdmin(1);
        $appointment = $this->makeAppointment(user_id: 99);

        $this->assertTrue($this->policy->delete($admin, $appointment));
    }

    // --- Staff restrictions ---

    public function test_staff_cannot_view_any(): void
    {
        $this->assertFalse($this->policy->viewAny($this->makeStaff()));
    }

    public function test_staff_cannot_create(): void
    {
        $this->assertFalse($this->policy->create($this->makeStaff()));
    }

    public function test_staff_can_view_own_appointment(): void
    {
        $staff       = $this->makeStaff(id: 5);
        $appointment = $this->makeAppointment(user_id: 5);

        $this->assertTrue($this->policy->view($staff, $appointment));
    }

    public function test_staff_cannot_view_others_appointment(): void
    {
        $staff       = $this->makeStaff(id: 5);
        $appointment = $this->makeAppointment(user_id: 9);

        $this->assertFalse($this->policy->view($staff, $appointment));
    }

    public function test_staff_can_update_own_appointment(): void
    {
        $staff       = $this->makeStaff(id: 5);
        $appointment = $this->makeAppointment(user_id: 5);

        $this->assertTrue($this->policy->update($staff, $appointment));
    }

    public function test_staff_cannot_update_others_appointment(): void
    {
        $staff       = $this->makeStaff(id: 5);
        $appointment = $this->makeAppointment(user_id: 9);

        $this->assertFalse($this->policy->update($staff, $appointment));
    }

    public function test_staff_cannot_delete(): void
    {
        $staff       = $this->makeStaff(id: 5);
        $appointment = $this->makeAppointment(user_id: 99);

        $this->assertFalse($this->policy->delete($staff, $appointment));
    }

    // --- Helpers ---

    private function makeAdmin(int $id = 1): User
    {
        $user = new User();
        $user->forceFill(['id' => $id, 'role' => UserRole::Admin]);

        return $user;
    }

    private function makeStaff(int $id = 2): User
    {
        $user = new User();
        $user->forceFill(['id' => $id, 'role' => UserRole::Staff]);

        return $user;
    }

    private function makeAppointment(int $user_id): Appointment
    {
        $appointment = new Appointment();
        $appointment->forceFill(['user_id' => $user_id]);

        return $appointment;
    }
}
