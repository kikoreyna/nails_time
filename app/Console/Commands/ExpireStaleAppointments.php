<?php

namespace App\Console\Commands;

use App\AppointmentBooking;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('appointments:expire')]
#[Description('Libera las citas con reserva temporal vencida')]
class ExpireStaleAppointments extends Command
{
    public function handle(AppointmentBooking $booking): int
    {
        $this->info("Citas expiradas: {$booking->expireStale()}");

        return self::SUCCESS;
    }
}
