<?php

namespace App;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Technician;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AppointmentAvailability
{
    public function __construct(private BusinessSchedule $schedule) {}

    /** @param Collection<int, Service> $services */
    public function availableSlots(string $date, Collection $services, ?int $technicianId = null): array
    {
        $duration = (int) $services->sum('duration_minutes');
        $interval = (int) config('booking.slot_interval_minutes');
        $now = Carbon::now((string) config('booking.timezone'));
        $technicians = $this->technicians($technicianId);
        $busy = $this->busyAppointments($date);
        $slots = [];

        foreach ($this->schedule->windows($date) as [$opening, $closing]) {
            for ($slot = $opening->copy(); $slot->copy()->addMinutes($duration)->lessThanOrEqualTo($closing); $slot->addMinutes($interval)) {
                $end = $slot->copy()->addMinutes($duration);

                if ($slot->lessThan($now) || $this->schedule->isBlocked($slot, $end)) {
                    continue;
                }

                if ($this->freeTechnician($technicians, $busy, $slot->format('H:i:s'), $end->format('H:i:s')) === null) {
                    continue;
                }

                $slots[] = [
                    'time' => $slot->format('H:i'),
                    'end' => $end->format('H:i'),
                ];
            }
        }

        return $slots;
    }

    /**
     * Devuelve la técnica libre para el bloque (la elegida o la primera disponible).
     * Debe invocarse dentro del candado del día para evitar dobles reservas.
     */
    public function assignTechnician(string $date, string $startTime, int $duration, ?int $technicianId = null): ?Technician
    {
        $timezone = (string) config('booking.timezone');
        $start = Carbon::parse($date.' '.$startTime, $timezone);
        $end = $start->copy()->addMinutes($duration);
        $interval = (int) config('booking.slot_interval_minutes');

        if ($start->lessThan(Carbon::now($timezone)) || $this->schedule->isBlocked($start, $end)) {
            return null;
        }

        $fitsWindow = collect($this->schedule->windows($date))->contains(
            fn (array $window): bool => $start->greaterThanOrEqualTo($window[0])
                && $end->lessThanOrEqualTo($window[1])
                && $window[0]->diffInMinutes($start) % $interval === 0
        );

        if (! $fitsWindow) {
            return null;
        }

        return $this->freeTechnician(
            $this->technicians($technicianId),
            $this->busyAppointments($date),
            $start->format('H:i:s'),
            $end->format('H:i:s'),
        );
    }

    /** @return Collection<int, Technician> */
    private function technicians(?int $technicianId): Collection
    {
        return Technician::query()
            ->where('is_active', true)
            ->when($technicianId, fn ($query) => $query->whereKey($technicianId))
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, Appointment> */
    private function busyAppointments(string $date): Collection
    {
        return Appointment::query()
            ->whereDate('appointment_date', $date)
            ->blocking()
            ->get(['id', 'technician_id', 'starts_at', 'ends_at']);
    }

    /**
     * @param  Collection<int, Technician>  $technicians
     * @param  Collection<int, Appointment>  $busy
     */
    private function freeTechnician(Collection $technicians, Collection $busy, string $start, string $end): ?Technician
    {
        // Las citas sin técnica asignada bloquean toda la agenda del bloque.
        if ($this->overlapsAny($busy->whereNull('technician_id'), null, $start, $end)) {
            return null;
        }

        return $technicians->first(
            fn (Technician $technician): bool => ! $this->overlapsAny($busy->where('technician_id', $technician->id), $technician->id, $start, $end),
        );
    }

    /** @param Collection<int, Appointment> $busy */
    private function overlapsAny(Collection $busy, ?int $technicianId, string $start, string $end): bool
    {
        return $busy
            ->filter(fn (Appointment $appointment): bool => $technicianId === null
                || $appointment->technician_id === null
                || $appointment->technician_id === $technicianId)
            ->contains(fn (Appointment $appointment): bool => $appointment->starts_at < $end && $appointment->ends_at > $start);
    }
}
