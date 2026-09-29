<?php

namespace App;

use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AppointmentAvailability
{
    /** @param Collection<int, Service> $services */
    public function availableSlots(string $date, Collection $services): array
    {
        $duration = (int) $services->sum('duration_minutes');
        $timezone = (string) config('booking.timezone');
        $opening = Carbon::parse($date.' '.config('booking.opening_time'), $timezone);
        $closing = Carbon::parse($date.' '.config('booking.closing_time'), $timezone);
        $now = Carbon::now($timezone);
        $slots = [];

        for ($slot = $opening->copy(); $slot->copy()->addMinutes($duration)->lessThanOrEqualTo($closing); $slot->addMinutes((int) config('booking.slot_interval_minutes'))) {
            if ($slot->lessThan($now) || $this->hasOverlap($date, $slot, $duration)) {
                continue;
            }

            $slots[] = [
                'time' => $slot->format('H:i'),
                'end' => $slot->copy()->addMinutes($duration)->format('H:i'),
            ];
        }

        return $slots;
    }

    public function isAvailable(string $date, string $startTime, int $duration): bool
    {
        $timezone = (string) config('booking.timezone');
        $start = Carbon::parse($date.' '.$startTime, $timezone);
        $opening = Carbon::parse($date.' '.config('booking.opening_time'), $timezone);
        $closing = Carbon::parse($date.' '.config('booking.closing_time'), $timezone);
        $end = $start->copy()->addMinutes($duration);
        $interval = (int) config('booking.slot_interval_minutes');

        if ($start->lessThan($opening) || $end->greaterThan($closing) || $start->lessThan(Carbon::now($timezone))) {
            return false;
        }

        if ($opening->diffInMinutes($start) % $interval !== 0) {
            return false;
        }

        return ! $this->hasOverlap($date, $start, $duration);
    }

    private function hasOverlap(string $date, Carbon $start, int $duration): bool
    {
        $end = $start->copy()->addMinutes($duration)->format('H:i:s');

        return Appointment::query()
            ->whereDate('appointment_date', $date)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start->format('H:i:s'))
            ->exists();
    }
}
