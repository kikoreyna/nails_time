<?php

namespace App\Http\Controllers;

use App\AppointmentAvailability;
use App\Mail\AppointmentConfirmation;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        return view('appointments.index', [
            'services' => Service::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
            'today' => Carbon::now(config('booking.timezone'))->toDateString(),
        ]);
    }

    public function availability(Request $request, AppointmentAvailability $availability): JsonResponse
    {
        $minimumDate = Carbon::now(config('booking.timezone'))->toDateString();

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$minimumDate],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['required', 'integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
        ]);

        $services = Service::query()->whereKey($validated['services'])->where('is_active', true)->get();

        return response()->json([
            'slots' => $availability->availableSlots($validated['date'], $services),
        ]);
    }

    public function store(Request $request, AppointmentAvailability $availability): RedirectResponse
    {
        $minimumDate = Carbon::now(config('booking.timezone'))->toDateString();

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$minimumDate],
            'start_time' => ['required', 'date_format:H:i'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
        ]);

        $services = Service::query()->whereKey($validated['service_ids'])->where('is_active', true)->get();
        $duration = (int) $services->sum('duration_minutes');
        $totalPrice = round((float) $services->sum(fn (Service $service): string => $service->price), 2);
        $date = $validated['appointment_date'];

        $appointment = DB::transaction(function () use ($availability, $date, $duration, $services, $totalPrice, $validated): Appointment {
            DB::table('appointment_day_locks')->insertOrIgnore([
                'appointment_date' => $date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('appointment_day_locks')
                ->where('appointment_date', $date)
                ->lockForUpdate()
                ->first();

            if (! $availability->isAvailable($date, $validated['start_time'], $duration)) {
                throw ValidationException::withMessages([
                    'start_time' => 'Ese horario ya no está disponible. Elige otra hora.',
                ]);
            }

            $appointment = Appointment::query()->create([
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'appointment_date' => $date,
                'starts_at' => $validated['start_time'],
                'ends_at' => Carbon::parse($date.' '.$validated['start_time'], config('booking.timezone'))
                    ->addMinutes($duration)
                    ->format('H:i:s'),
                'total_minutes' => $duration,
                'total_price' => $totalPrice,
                'status' => 'confirmed',
            ]);

            $appointment->services()->attach($services->mapWithKeys(fn (Service $service): array => [
                $service->id => [
                    'service_name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'price' => $service->price,
                ],
            ])->all());

            return $appointment;
        });

        $appointment->load('services');
        Mail::to($appointment->customer_email)->send(new AppointmentConfirmation($appointment));

        return redirect()->route('appointments.index')->with('success', 'Tu cita quedó confirmada. Te enviamos los detalles por correo.');
    }
}
