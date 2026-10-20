<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentReviewStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\PaymentProof;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    private const TECHNICIANS = [
        'Ana', 'Valeria', 'Camila', 'Daniela', 'Fernanda',
        'Gabriela', 'Isabel', 'Jimena', 'Karla', 'Luisa',
    ];

    private const START_HOURS = [10, 12, 14, 16, 18];

    /** @var array<string, bool> */
    private array $usedSlots = [];

    public function run(): void
    {
        $this->call(ServiceSeeder::class);

        $services = Service::query()->where('is_active', true)->get();
        $technicians = collect(self::TECHNICIANS)->map(
            fn (string $name) => Technician::query()->firstOrCreate(['name' => $name], ['is_active' => true])
        );
        $admin = User::query()->where('is_admin', true)->first();

        $clients = collect(range(1, 50))->map(fn (int $number) => Client::query()->updateOrCreate(
            ['email' => "cliente{$number}@demo.test"],
            [
                'name' => fake('es_MX')->name(),
                'phone' => fake()->numerify('55########'),
                'is_blacklisted' => $number > 47,
            ]
        ));

        // Distribución de citas por estado: [estado, cantidad, momento]
        $plan = [
            ['completed', 70, 'past'],
            ['no_show', 12, 'past'],
            ['cancelled', 10, 'any'],
            ['confirmed_past', 8, 'past'],
            ['confirmed', 40, 'future'],
            ['pending_proof', 8, 'future'],
            ['pending_review', 10, 'future'],
            ['rejected', 8, 'any'],
            ['expired', 8, 'past'],
        ];

        foreach ($plan as [$kind, $count, $when]) {
            for ($i = 0; $i < $count; $i++) {
                $this->createAppointment($kind, $when, $clients->random(), $technicians->random(), $services, $admin);
            }
        }
    }

    private function createAppointment(string $kind, string $when, Client $client, Technician $technician, $services, ?User $admin): void
    {
        $chosen = $services->random(fake()->numberBetween(1, 2))->values();
        $minutes = min($chosen->sum('duration_minutes'), 120);
        $price = $chosen->sum('price');

        [$date, $hour] = $this->freeSlot($technician, $when);
        $start = Carbon::parse($date)->setTime($hour, 0);
        $end = $start->copy()->addMinutes($minutes);

        $needsDeposit = in_array($kind, ['pending_proof', 'pending_review', 'rejected', 'expired'], true)
            || fake()->boolean(25);

        $status = match ($kind) {
            'completed' => AppointmentStatus::Completed,
            'no_show' => AppointmentStatus::NoShow,
            'cancelled' => AppointmentStatus::Cancelled,
            'confirmed_past', 'confirmed' => AppointmentStatus::Confirmed,
            'pending_proof', 'pending_review' => AppointmentStatus::PendingPaymentValidation,
            'rejected' => AppointmentStatus::Rejected,
            'expired' => AppointmentStatus::Expired,
        };

        $appointment = Appointment::query()->create([
            'client_id' => $client->id,
            'technician_id' => $technician->id,
            'customer_name' => $client->name,
            'customer_email' => $client->email,
            'customer_phone' => $client->phone,
            'appointment_date' => $date,
            'starts_at' => $start->format('H:i:s'),
            'ends_at' => $end->format('H:i:s'),
            'total_minutes' => $minutes,
            'total_price' => $price,
            'status' => $status,
            'deposit_required' => $needsDeposit,
            'deposit_amount' => $needsDeposit ? round($price * config('booking.deposit_percentage', 30) / 100, 2) : 0,
            'expires_at' => match ($kind) {
                'pending_proof' => now()->addMinutes(fake()->numberBetween(5, 20)),
                'pending_review' => now()->addMinutes(fake()->numberBetween(30, 170)),
                'expired' => $start->copy()->subDay(),
                default => null,
            },
        ]);

        $appointment->services()->attach($chosen->mapWithKeys(fn (Service $service) => [
            $service->id => [
                'service_name' => $service->name,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
            ],
        ])->all());

        $reviewStatus = match ($kind) {
            'pending_review' => PaymentReviewStatus::Pending,
            'rejected' => PaymentReviewStatus::Rejected,
            'completed', 'no_show', 'confirmed_past', 'confirmed', 'cancelled' => $needsDeposit ? PaymentReviewStatus::Approved : null,
            default => null,
        };

        if ($reviewStatus) {
            $reviewed = $reviewStatus !== PaymentReviewStatus::Pending;

            PaymentProof::query()->create([
                'appointment_id' => $appointment->id,
                'image_path' => 'payment-proofs/demo.png',
                'uploaded_at' => $start->copy()->subDays(2),
                'reviewed_by_admin_id' => $reviewed ? $admin?->id : null,
                'reviewed_at' => $reviewed ? $start->copy()->subDays(2)->addHour() : null,
                'review_status' => $reviewStatus,
                'rejection_reason' => $reviewStatus === PaymentReviewStatus::Rejected ? 'El monto del comprobante no coincide.' : null,
            ]);
        }
    }

    /**
     * Busca un horario libre para la manicurista sin traslapar citas.
     *
     * @return array{0: string, 1: int}
     */
    private function freeSlot(Technician $technician, string $when): array
    {
        do {
            $offset = match ($when) {
                'past' => -fake()->numberBetween(1, 60),
                'future' => fake()->numberBetween(1, 21),
                default => fake()->numberBetween(-30, 21),
            };
            $date = today()->addDays($offset)->toDateString();
            $hour = fake()->randomElement(self::START_HOURS);
            $key = "{$technician->id}|{$date}|{$hour}";
        } while (isset($this->usedSlots[$key]) || Appointment::query()
            ->where('technician_id', $technician->id)
            ->whereDate('appointment_date', $date)
            ->where('starts_at', sprintf('%02d:00:00', $hour))
            ->exists());

        $this->usedSlots[$key] = true;

        return [$date, $hour];
    }
}
