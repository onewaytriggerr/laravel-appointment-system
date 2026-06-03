<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'phone', 'timezone', 'opening_time', 'closing_time'];

    // A branch has many staff members (users)
    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // A branch has many appointments
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
