<?php

namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum DayOfWeek: int implements HasLabel
{
    case Monday    = 1;
    case Tuesday   = 2;
    case Wednesday = 3;
    case Thursday  = 4;
    case Friday    = 5;
    case Saturday  = 6;
    case Sunday    = 0;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Monday    => 'Monday',
            self::Tuesday   => 'Tuesday',
            self::Wednesday => 'Wednesday',
            self::Thursday  => 'Thursday',
            self::Friday    => 'Friday',
            self::Saturday  => 'Saturday',
            self::Sunday    => 'Sunday',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
