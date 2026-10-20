<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('##########'),
            'appointment_date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'starts_at' => '10:00:00',
            'ends_at' => '11:00:00',
            'total_minutes' => 60,
            'total_price' => 500,
            'status' => AppointmentStatus::Confirmed,
        ];
    }

    public function pendingPayment(int $minutes = 20): static
    {
        return $this->state([
            'status' => AppointmentStatus::PendingPaymentValidation,
            'deposit_required' => true,
            'deposit_amount' => 150,
            'expires_at' => now()->addMinutes($minutes),
        ]);
    }
}
