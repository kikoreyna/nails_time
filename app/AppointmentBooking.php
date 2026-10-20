<?php

namespace App;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentReviewStatus;
use App\Mail\AppointmentConfirmation;
use App\Mail\AppointmentRejected;
use App\Mail\PaymentProofSubmitted;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\PaymentProof;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AppointmentBooking
{
    public function __construct(
        private AppointmentAvailability $availability,
        private DepositPolicy $depositPolicy,
    ) {}

    /**
     * @param  array{customer_name: string, customer_email: string, customer_phone: string, appointment_date: string, start_time: string, technician_id?: int|null}  $data
     * @param  Collection<int, Service>  $services
     */
    public function book(array $data, Collection $services): Appointment
    {
        $duration = (int) $services->sum('duration_minutes');
        $totalPrice = round((float) $services->sum(fn (Service $service): string => $service->price), 2);
        $date = $data['appointment_date'];

        $appointment = DB::transaction(function () use ($data, $date, $duration, $services, $totalPrice): Appointment {
            $this->lockDay($date);

            $technician = $this->availability->assignTechnician($date, $data['start_time'], $duration, $data['technician_id'] ?? null);

            if ($technician === null) {
                throw ValidationException::withMessages([
                    'start_time' => 'Ese horario ya no está disponible. Elige otra hora.',
                ]);
            }

            $client = Client::query()->createOrFirst(
                ['email' => mb_strtolower($data['customer_email'])],
                ['name' => $data['customer_name'], 'phone' => $data['customer_phone']],
            );

            $requiresDeposit = $this->depositPolicy->requiresDeposit($client);

            $appointment = Appointment::query()->create([
                'client_id' => $client->id,
                'technician_id' => $technician->id,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'appointment_date' => $date,
                'starts_at' => $data['start_time'],
                'ends_at' => Carbon::parse($date.' '.$data['start_time'], config('booking.timezone'))
                    ->addMinutes($duration)
                    ->format('H:i:s'),
                'total_minutes' => $duration,
                'total_price' => $totalPrice,
                'status' => $requiresDeposit ? AppointmentStatus::PendingPaymentValidation : AppointmentStatus::Confirmed,
                'deposit_required' => $requiresDeposit,
                'deposit_amount' => $requiresDeposit ? $this->depositPolicy->depositAmount($totalPrice) : 0,
                'expires_at' => $requiresDeposit ? now()->addMinutes((int) config('booking.proof_upload_minutes')) : null,
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

        if (! $appointment->deposit_required) {
            Mail::to($appointment->customer_email)->send(new AppointmentConfirmation($appointment));
        }

        return $appointment;
    }

    public function attachProof(Appointment $appointment, UploadedFile $file): PaymentProof
    {
        $disk = (string) config('booking.proof.disk');
        $path = null;

        try {
            $proof = DB::transaction(function () use ($appointment, $file, $disk, &$path): PaymentProof {
                $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

                if (
                    $locked->status !== AppointmentStatus::PendingPaymentValidation
                    || ! $locked->deposit_required
                    || $locked->expires_at === null
                    || $locked->expires_at->isPast()
                ) {
                    throw ValidationException::withMessages([
                        'proof' => 'El tiempo para subir tu comprobante terminó y el horario fue liberado.',
                    ]);
                }

                if ($locked->paymentProof()->exists()) {
                    throw ValidationException::withMessages(['proof' => 'Ya recibimos un comprobante para esta cita.']);
                }

                $path = $file->store((string) config('booking.proof.directory'), $disk);

                $proof = $locked->paymentProof()->create([
                    'image_path' => $path,
                    'uploaded_at' => now(),
                    'review_status' => PaymentReviewStatus::Pending,
                ]);

                $locked->update(['expires_at' => now()->addMinutes((int) config('booking.admin_review_minutes'))]);

                return $proof;
            });
        } catch (\Throwable $exception) {
            if ($path !== null) {
                Storage::disk($disk)->delete($path);
            }

            throw $exception;
        }

        $adminEmail = config('booking.admin_email');

        if ($adminEmail) {
            Mail::to($adminEmail)->send(new PaymentProofSubmitted($appointment->fresh(['services']), $proof));
        } else {
            report(new \RuntimeException('BOOKING_ADMIN_EMAIL no está configurado; no se notificó el comprobante #'.$proof->id));
        }

        return $proof;
    }

    public function approve(Appointment $appointment, User $admin): Appointment
    {
        $approved = DB::transaction(function () use ($appointment, $admin): Appointment {
            $locked = $this->lockPendingWithProof($appointment);

            if ($locked->expires_at === null || $locked->expires_at->isPast()) {
                $locked->update(['status' => AppointmentStatus::Expired]);

                return $locked;
            }

            $locked->paymentProof->update([
                'review_status' => PaymentReviewStatus::Approved,
                'reviewed_by_admin_id' => $admin->id,
                'reviewed_at' => now(),
            ]);
            $locked->update(['status' => AppointmentStatus::Confirmed, 'expires_at' => null]);

            return $locked;
        });

        if ($approved->status === AppointmentStatus::Expired) {
            throw ValidationException::withMessages([
                'appointment' => 'La reserva venció antes de la aprobación y el horario fue liberado.',
            ]);
        }

        Mail::to($approved->customer_email)->send(new AppointmentConfirmation($approved->load('services')));

        return $approved;
    }

    public function reject(Appointment $appointment, User $admin, string $reason): Appointment
    {
        $rejected = DB::transaction(function () use ($appointment, $admin, $reason): Appointment {
            $locked = $this->lockPendingWithProof($appointment);

            $locked->paymentProof->update([
                'review_status' => PaymentReviewStatus::Rejected,
                'reviewed_by_admin_id' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);
            $locked->update(['status' => AppointmentStatus::Rejected, 'expires_at' => null]);

            return $locked;
        });

        Mail::to($rejected->customer_email)->send(new AppointmentRejected($rejected->load('services'), $reason));

        return $rejected;
    }

    /**
     * Libera las reservas temporales vencidas. Devuelve cuántas citas expiraron.
     */
    public function expireStale(): int
    {
        return Appointment::query()
            ->where('status', AppointmentStatus::PendingPaymentValidation)
            ->where('expires_at', '<=', now())
            ->update(['status' => AppointmentStatus::Expired]);
    }

    private function lockPendingWithProof(Appointment $appointment): Appointment
    {
        $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
        $locked->load('paymentProof');

        if (
            $locked->status !== AppointmentStatus::PendingPaymentValidation
            || $locked->paymentProof?->review_status !== PaymentReviewStatus::Pending
        ) {
            throw ValidationException::withMessages([
                'appointment' => 'Esta cita ya no está pendiente de revisión.',
            ]);
        }

        return $locked;
    }

    private function lockDay(string $date): void
    {
        DB::table('appointment_day_locks')->insertOrIgnore([
            'appointment_date' => $date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('appointment_day_locks')
            ->where('appointment_date', $date)
            ->lockForUpdate()
            ->first();
    }
}
