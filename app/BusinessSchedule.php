<?php

namespace App;

use App\Models\BlockedTime;
use App\Models\BusinessHour;
use Illuminate\Support\Carbon;

class BusinessSchedule
{
    /**
     * Horario semanal indexado por día ISO (1 = lunes ... 7 = domingo).
     * Los días sin configurar usan los valores por defecto de config/booking.php.
     *
     * @return array<int, array{is_open: bool, opens_at: string, closes_at: string, break_starts_at: ?string, break_ends_at: ?string}>
     */
    public function week(): array
    {
        $saved = BusinessHour::query()->get()->keyBy('weekday');
        $week = [];

        foreach (range(1, 7) as $weekday) {
            $row = $saved->get($weekday);

            $week[$weekday] = [
                'is_open' => $row?->is_open ?? true,
                'opens_at' => substr((string) ($row?->opens_at ?? config('booking.opening_time')), 0, 5),
                'closes_at' => substr((string) ($row?->closes_at ?? config('booking.closing_time')), 0, 5),
                'break_starts_at' => $row?->break_starts_at ? substr($row->break_starts_at, 0, 5) : null,
                'break_ends_at' => $row?->break_ends_at ? substr($row->break_ends_at, 0, 5) : null,
            ];
        }

        return $week;
    }

    /**
     * Ventanas de atención del día (una si es horario corrido, dos si hay pausa).
     *
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    public function windows(string $date): array
    {
        $timezone = (string) config('booking.timezone');
        $day = $this->week()[Carbon::parse($date, $timezone)->dayOfWeekIso];

        if (! $day['is_open']) {
            return [];
        }

        $at = fn (string $time): Carbon => Carbon::parse($date.' '.$time, $timezone);
        $opens = $at($day['opens_at']);
        $closes = $at($day['closes_at']);

        if ($day['break_starts_at'] && $day['break_ends_at']) {
            return array_values(array_filter([
                [$opens, $at($day['break_starts_at'])],
                [$at($day['break_ends_at']), $closes],
            ], fn (array $window): bool => $window[0]->lessThan($window[1])));
        }

        return [[$opens, $closes]];
    }

    /**
     * Indica si el bloque [start, end) cae en una fecha u hora bloqueada.
     */
    public function isBlocked(Carbon $start, Carbon $end): bool
    {
        $timezone = (string) config('booking.timezone');
        $date = $start->toDateString();

        return BlockedTime::query()->whereDate('date', $date)->get()->contains(function (BlockedTime $block) use ($date, $timezone, $start, $end): bool {
            if ($block->starts_at === null || $block->ends_at === null) {
                return true;
            }

            return $start->lessThan(Carbon::parse($date.' '.$block->ends_at, $timezone))
                && $end->greaterThan(Carbon::parse($date.' '.$block->starts_at, $timezone));
        });
    }
}
