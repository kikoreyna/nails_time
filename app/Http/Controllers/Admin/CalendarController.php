<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Technician;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'technician_id' => ['nullable', 'integer', 'exists:technicians,id'],
        ]);

        $timezone = (string) config('booking.timezone');
        $reference = Carbon::parse($validated['date'] ?? Carbon::now($timezone)->toDateString(), $timezone);
        $weekStart = $reference->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);

        // Las manicuristas solo ven su agenda; el administrador puede filtrar.
        $technicianId = $user->is_admin ? ($validated['technician_id'] ?? null) : $user->technician_id;

        $appointments = Appointment::query()
            ->with(['services', 'technician'])
            ->whereBetween('appointment_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where(function (Builder $query): void {
                $query->blocking()->orWhereIn('status', [AppointmentStatus::Completed, AppointmentStatus::NoShow]);
            })
            ->when($technicianId, fn (Builder $query) => $query->where('technician_id', $technicianId))
            ->orderBy('appointment_date')
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Appointment $appointment): string => $appointment->appointment_date->toDateString());

        $days = collect(range(0, 6))->map(fn (int $offset): Carbon => $weekStart->copy()->addDays($offset));

        return view('admin.calendar', [
            'days' => $days,
            'appointments' => $appointments,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'previousWeek' => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek' => $weekStart->copy()->addWeek()->toDateString(),
            'today' => Carbon::now($timezone)->toDateString(),
            'technicians' => $user->is_admin ? Technician::query()->orderBy('name')->get() : collect(),
            'technicianId' => $technicianId,
            'ownTechnician' => $user->is_admin ? null : $user->technician,
        ]);
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->is_admin || $appointment->technician_id === $user->technician_id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in([AppointmentStatus::Completed->value, AppointmentStatus::NoShow->value])],
        ]);

        $updated = DB::transaction(function () use ($appointment, $validated): bool {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $startsAt = Carbon::parse($locked->appointment_date->toDateString().' '.$locked->starts_at, config('booking.timezone'));

            if ($locked->status !== AppointmentStatus::Confirmed || $startsAt->isFuture()) {
                return false;
            }

            $locked->update(['status' => $validated['status']]);

            return true;
        });

        if (! $updated) {
            return back()->withErrors(['status' => 'Solo se pueden cerrar citas confirmadas que ya comenzaron.']);
        }

        return back()->with('success', 'Cita actualizada.');
    }
}
