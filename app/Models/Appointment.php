<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'technician_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'appointment_date',
        'starts_at',
        'ends_at',
        'total_minutes',
        'total_price',
        'status',
        'deposit_required',
        'deposit_amount',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'total_price' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'deposit_required' => 'boolean',
            'expires_at' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    /**
     * Citas que ocupan agenda: confirmadas o pendientes con reserva vigente.
     */
    public function scopeBlocking(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->where('status', AppointmentStatus::Confirmed)
                ->orWhere(function (Builder $query): void {
                    $query->where('status', AppointmentStatus::PendingPaymentValidation)
                        ->where('expires_at', '>', now());
                });
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function paymentProof(): HasOne
    {
        return $this->hasOne(PaymentProof::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot(['service_name', 'duration_minutes', 'price'])
            ->withTimestamps();
    }
}
