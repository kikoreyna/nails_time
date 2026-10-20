<?php

namespace App\Http\Controllers;

use App\AppointmentAvailability;
use App\AppointmentBooking;
use App\Enums\AppointmentStatus;
use App\Http\Requests\StorePaymentProofRequest;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Technician;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
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
            'technicians' => Technician::query()->where('is_active', true)->orderBy('name')->get(),
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
            'technician_id' => ['nullable', 'integer', Rule::exists('technicians', 'id')->where('is_active', true)],
        ]);

        $services = Service::query()->whereKey($validated['services'])->where('is_active', true)->get();

        return response()->json([
            'slots' => $availability->availableSlots($validated['date'], $services, $validated['technician_id'] ?? null),
        ]);
    }

    public function store(Request $request, AppointmentBooking $booking): RedirectResponse
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
            'technician_id' => ['nullable', 'integer', Rule::exists('technicians', 'id')->where('is_active', true)],
        ]);

        $services = Service::query()->whereKey($validated['service_ids'])->where('is_active', true)->get();
        $appointment = $booking->book($validated, $services);

        if ($appointment->deposit_required) {
            return redirect(URL::signedRoute('appointments.payment', $appointment));
        }

        return redirect()->route('appointments.index')->with('success', 'Tu cita quedó confirmada. Te enviamos los detalles por correo.');
    }

    public function payment(Appointment $appointment): View
    {
        return view('appointments.payment', [
            'appointment' => $appointment->load(['services', 'paymentProof']),
            'bank' => config('booking.bank'),
            'uploadUrl' => URL::signedRoute('appointments.proof.store', $appointment),
            'canUpload' => $appointment->status === AppointmentStatus::PendingPaymentValidation
                && $appointment->paymentProof === null
                && $appointment->expires_at?->isFuture(),
        ]);
    }

    public function storeProof(StorePaymentProofRequest $request, Appointment $appointment, AppointmentBooking $booking): RedirectResponse
    {
        $booking->attachProof($appointment, $request->file('proof'));

        return redirect(URL::signedRoute('appointments.payment', $appointment))
            ->with('success', 'Recibimos tu comprobante. Te avisaremos por correo cuando lo validemos.');
    }
}
