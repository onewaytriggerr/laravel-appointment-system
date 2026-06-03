<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'branch_id'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,   // cast to our PHP Enum
        ];
    }

    // Staff belongs to a branch
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Staff can have many appointments assigned to them
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    // Check if the user is an admin
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    // Check if the user is a staff
    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') return $this->isAdmin();
        if ($panel->getId() === 'staff') return $this->isStaff();
        
        return false;
    }
}
