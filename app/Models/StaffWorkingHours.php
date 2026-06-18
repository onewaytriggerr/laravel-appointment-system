<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\DayOfWeek;

class StaffWorkingHours extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'day_of_week', 'start_time', 'end_time'];

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
        ];
    }

    // Working hours belong to one particular staff
    public function staff(): HasMany
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
