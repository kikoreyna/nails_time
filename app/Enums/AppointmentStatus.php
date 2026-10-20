<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PendingPaymentValidation = 'pending_payment_validation';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Expired = 'expired';
    case NoShow = 'no_show';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingPaymentValidation => 'Pendiente de validación',
            self::Confirmed => 'Confirmada',
            self::Cancelled => 'Cancelada',
            self::Completed => 'Completada',
            self::Expired => 'Expirada',
            self::NoShow => 'No asistió',
            self::Rejected => 'Rechazada',
        };
    }
}
