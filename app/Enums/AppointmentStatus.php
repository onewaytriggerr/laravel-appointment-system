<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AppointmentStatus: string implements HasLabel, HasColor
{
    case Pending    = 'pending';
    case Confirmed  = 'confirmed';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';
    case NoShow     = 'no_show';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::Confirmed  => 'Confirmed',
            self::InProgress => 'In Progress',
            self::Completed  => 'Completed',
            self::Cancelled  => 'Cancelled',
            self::NoShow     => 'No Show',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending    => 'warning',
            self::Confirmed  => 'info',
            self::InProgress => 'primary',
            self::Completed  => 'success',
            self::Cancelled  => 'danger',
            self::NoShow     => 'gray',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    // Determine if this status should block the staff's availability for new appointments
    public function blocksAvailability(): bool
    {
        return match($this) {
            self::Pending, self::Confirmed, self::InProgress, self::Completed => true,
            self::Cancelled, self::NoShow => false,
        };
    }

    // Allowed status transitions for appointments
    public function allowedTransitions (): array
    {
        return match($this) {
            self::Pending    => [self::Confirmed, self::Cancelled],
            self::Confirmed  => [self::InProgress, self::NoShow, self::Cancelled],
            self::InProgress => [self::Completed],
            self::Completed  => [],
            self::Cancelled  => [],
            self::NoShow     => [],
        };
    }

    // Helper function to get options for select fields in Filament forms
    public function allowedTransitionsOptions(): array
    {
        return collect($this->allowedTransitions())
            ->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()])
            ->toArray();
    }

    // Function for checking if a transition from current status to a new status is allowed
    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions());
    }
}
