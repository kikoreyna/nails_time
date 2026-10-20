<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockedTimeRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\BlockedTime;
use App\Models\BusinessHour;
use Illuminate\Http\RedirectResponse;

class ScheduleController extends Controller
{
    public function update(UpdateScheduleRequest $request): RedirectResponse
    {
        foreach ($request->validated('days') as $weekday => $day) {
            BusinessHour::query()->updateOrCreate(['weekday' => (int) $weekday], [
                'is_open' => (bool) ($day['is_open'] ?? false),
                'opens_at' => $day['opens_at'],
                'closes_at' => $day['closes_at'],
                'break_starts_at' => $day['break_starts_at'] ?? null,
                'break_ends_at' => $day['break_ends_at'] ?? null,
            ]);
        }

        return redirect()->route('admin.settings.edit')->with('success', 'Horario actualizado.');
    }

    public function storeBlock(BlockedTimeRequest $request): RedirectResponse
    {
        BlockedTime::query()->create($request->validated());

        return redirect()->route('admin.settings.edit')->with('success', 'Bloqueo agregado.');
    }

    public function destroyBlock(BlockedTime $blockedTime): RedirectResponse
    {
        $blockedTime->delete();

        return redirect()->route('admin.settings.edit')->with('success', 'Bloqueo eliminado.');
    }
}
