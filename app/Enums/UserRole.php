<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum UserRole: string implements HasLabel, HasColor
{
    case Admin = 'admin';
    case Staff = 'staff';

    public function getLabel(): ?string
    {
        return match($this) {
            self::Admin => 'Administrator',
            self::Staff => 'Staff',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::Admin => 'danger',
            self::Staff => 'info',
        };
    }
    
    public function label(): string
    {
        return $this->getLabel();
    }
}
