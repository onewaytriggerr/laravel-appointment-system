<?php

namespace Tests\Unit\Enums;

use App\Enums\AppointmentStatus;
use PHPUnit\Framework\TestCase;

class AppointmentStatusTest extends TestCase
{
    // --- blocksAvailability() tests ---
    public function test_pending_blocks_availability(): void
    {
        $this->assertTrue(AppointmentStatus::Pending->blocksAvailability());
    }

    public function test_confirmed_blocks_availability(): void
    {
        $this->assertTrue(AppointmentStatus::Confirmed->blocksAvailability());
    }

    public function test_in_progress_blocks_availability(): void
    {
        $this->assertTrue(AppointmentStatus::InProgress->blocksAvailability());
    }

    public function test_completed_blocks_availability(): void
    {
        $this->assertTrue(AppointmentStatus::Completed->blocksAvailability());
    }

    public function test_cancelled_does_not_block_availability(): void
    {
        $this->assertFalse(AppointmentStatus::Cancelled->blocksAvailability());
    }

    public function test_no_show_does_not_block_availability(): void
    {
        $this->assertFalse(AppointmentStatus::NoShow->blocksAvailability());
    }

    public function test_all_statuses_return_bool_from_blocks_availability(): void
    {
        foreach (AppointmentStatus::cases() as $status) {
            $this->assertIsBool($status->blocksAvailability(), "blocksAvailability() on {$status->value} must return bool");
        }
    }

    // --- allowedTransitions() tests ---
    public function test_terminal_states_have_no_allowed_transitions(): void
    {
        $this->assertSame([], AppointmentStatus::Completed->allowedTransitions());
        $this->assertSame([], AppointmentStatus::Cancelled->allowedTransitions());
        $this->assertSame([], AppointmentStatus::NoShow->allowedTransitions());
    }

    public function test_in_progress_can_only_transition_to_completed(): void
    {
        $this->assertSame([AppointmentStatus::Completed], AppointmentStatus::InProgress->allowedTransitions());
    }

    // --- Transition matrix via data provider ---

    public static function transitionProvider(): array
    {
        return [
            'pending -> confirmed (allowed)'     => [AppointmentStatus::Pending,    AppointmentStatus::Confirmed,  true],
            'pending -> cancelled (allowed)'     => [AppointmentStatus::Pending,    AppointmentStatus::Cancelled,  true],
            'pending -> in_progress (denied)'    => [AppointmentStatus::Pending,    AppointmentStatus::InProgress, false],
            'pending -> no_show (denied)'        => [AppointmentStatus::Pending,    AppointmentStatus::NoShow,     false],
            'pending -> completed (denied)'      => [AppointmentStatus::Pending,    AppointmentStatus::Completed,  false],
            'confirmed -> in_progress (allowed)' => [AppointmentStatus::Confirmed,  AppointmentStatus::InProgress, true],
            'confirmed -> no_show (allowed)'     => [AppointmentStatus::Confirmed,  AppointmentStatus::NoShow,     true],
            'confirmed -> cancelled (allowed)'   => [AppointmentStatus::Confirmed,  AppointmentStatus::Cancelled,  true],
            'confirmed -> pending (denied)'      => [AppointmentStatus::Confirmed,  AppointmentStatus::Pending,    false],
            'confirmed -> completed (denied)'    => [AppointmentStatus::Confirmed,  AppointmentStatus::Completed,  false],
            'in_progress -> completed (allowed)' => [AppointmentStatus::InProgress, AppointmentStatus::Completed,  true],
            'in_progress -> pending (denied)'    => [AppointmentStatus::InProgress, AppointmentStatus::Pending,    false],
            'in_progress -> confirmed (denied)'  => [AppointmentStatus::InProgress, AppointmentStatus::Confirmed,  false],
            'in_progress -> cancelled (denied)'  => [AppointmentStatus::InProgress, AppointmentStatus::Cancelled,  false],
            'completed -> pending (denied)'      => [AppointmentStatus::Completed,  AppointmentStatus::Pending,    false],
            'completed -> confirmed (denied)'    => [AppointmentStatus::Completed,  AppointmentStatus::Confirmed,  false],
            'cancelled -> pending (denied)'      => [AppointmentStatus::Cancelled,  AppointmentStatus::Pending,    false],
            'cancelled -> confirmed (denied)'    => [AppointmentStatus::Cancelled,  AppointmentStatus::Confirmed,  false],
            'no_show -> pending (denied)'        => [AppointmentStatus::NoShow,     AppointmentStatus::Pending,    false],
            'no_show -> confirmed (denied)'      => [AppointmentStatus::NoShow,     AppointmentStatus::Confirmed,  false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('transitionProvider')]
    public function test_transition_matrix(
        AppointmentStatus $from,
        AppointmentStatus $to,
        bool $expected
    ): void {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }
}
