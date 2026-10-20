<?php

namespace App\Http\Controllers\Admin;

use App\AppointmentBooking;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\PaymentProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReviewController extends Controller
{
    public function index(): View
    {
        $withRelations = ['services', 'technician', 'paymentProof'];

        return view('admin.appointments', [
            'pending' => Appointment::query()
                ->with($withRelations)
                ->where('status', AppointmentStatus::PendingPaymentValidation)
                ->orderBy('expires_at')
                ->get(),
            'recent' => Appointment::query()
                ->with($withRelations)
                ->where('status', '!=', AppointmentStatus::PendingPaymentValidation)
                ->latest('id')
                ->limit(25)
                ->get(),
        ]);
    }

    public function proof(PaymentProof $proof): StreamedResponse
    {
        $disk = Storage::disk((string) config('booking.proof.disk'));

        abort_unless($disk->exists($proof->image_path), 404);

        return $disk->response($proof->image_path, null, [
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approve(Request $request, Appointment $appointment, AppointmentBooking $booking): RedirectResponse
    {
        $booking->approve($appointment, $request->user());

        return back()->with('success', 'Cita confirmada y cliente notificado.');
    }

    public function reject(Request $request, Appointment $appointment, AppointmentBooking $booking): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $booking->reject($appointment, $request->user(), $validated['reason']);

        return back()->with('success', 'Comprobante rechazado, horario liberado y cliente notificado.');
    }
}
