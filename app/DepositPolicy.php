<?php

namespace App;

use App\Enums\AppointmentStatus;
use App\Models\Client;

class DepositPolicy
{
    /**
     * Cliente registrado: al menos una cita completada, sin lista negra
     * y sin ausencias recientes.
     */
    public function isRegistered(Client $client): bool
    {
        if ($client->is_blacklisted) {
            return false;
        }

        $hasCompletedAppointment = $client->appointments()
            ->where('status', AppointmentStatus::Completed)
            ->exists();

        if (! $hasCompletedAppointment) {
            return false;
        }

        return ! $client->appointments()
            ->where('status', AppointmentStatus::NoShow)
            ->whereDate('appointment_date', '>=', now()->subDays((int) config('booking.no_show_lookback_days'))->toDateString())
            ->exists();
    }

    public function requiresDeposit(Client $client): bool
    {
        return ! $this->isRegistered($client);
    }

    public function depositAmount(float $totalPrice): float
    {
        return round($totalPrice * (int) config('booking.deposit_percentage') / 100, 2);
    }
}
