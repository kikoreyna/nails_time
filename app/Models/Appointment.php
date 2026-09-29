<?php

namespace App\Models;

use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_email',
        'customer_phone',
        'appointment_date',
        'starts_at',
        'ends_at',
        'total_minutes',
        'total_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'total_price' => 'decimal:2',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot(['service_name', 'duration_minutes', 'price'])
            ->withTimestamps();
    }
}
