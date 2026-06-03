<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\AppointmentStatus;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id', 'user_id', 'customer_id', 'service_id', 'starts_at', 'ends_at', 'status', 'cancellation_reason'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
            'status'    => AppointmentStatus::class,
        ];
    }

    // Appointment belongs to a branch
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Appointment belongs to a staff
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Appointment belongs to a customer
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Appointment belongs to a service
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
