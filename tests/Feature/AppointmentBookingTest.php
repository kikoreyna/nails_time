<?php

namespace Tests\Feature;

use App\Mail\AppointmentConfirmation;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_page_shows_service_and_date_selection(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Agenda tu cita')
            ->assertSee('Fecha de tu cita')
            ->assertSee('Selecciona tus servicios');
    }

    public function test_availability_uses_total_service_duration_and_excludes_busy_times(): void
    {
        $this->travelTo('2026-09-28 08:00:00');
        $manicure = Service::factory()->create(['duration_minutes' => 45]);
        $pedicure = Service::factory()->create(['duration_minutes' => 30]);

        Appointment::factory()->create([
            'appointment_date' => '2026-09-29',
            'starts_at' => '09:30:00',
            'ends_at' => '10:15:00',
        ]);

        $response = $this->getJson('/availability?date=2026-09-29&services[]='.$manicure->id.'&services[]='.$pedicure->id);

        $response->assertOk()
            ->assertJsonMissing(['time' => '09:00'])
            ->assertJsonFragment(['time' => '10:30', 'end' => '11:45']);
    }

    public function test_customer_can_book_without_an_account_and_receives_confirmation_email(): void
    {
        Mail::fake();
        $this->travelTo('2026-09-28 08:00:00');
        $manicure = Service::factory()->create(['duration_minutes' => 45, 'price' => 350]);
        $pedicure = Service::factory()->create(['duration_minutes' => 30, 'price' => 420]);

        $response = $this->post('/appointments', [
            'customer_name' => 'Ana Perez',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '5551234567',
            'appointment_date' => '2026-09-29',
            'start_time' => '11:00',
            'service_ids' => [$manicure->id, $pedicure->id],
        ]);

        $response->assertRedirect(route('appointments.index'))
            ->assertSessionHas('success');

        $appointment = Appointment::query()->firstOrFail();
        $this->assertSame('12:15:00', $appointment->ends_at);
        $this->assertSame(75, $appointment->total_minutes);
        $this->assertSame('770.00', $appointment->total_price);
        $this->assertCount(2, $appointment->services);

        Mail::assertSent(AppointmentConfirmation::class, fn (AppointmentConfirmation $mail): bool => $mail->hasTo('ana@example.com'));
    }

    public function test_customer_cannot_book_a_time_that_overlaps_an_existing_appointment(): void
    {
        $this->travelTo('2026-09-28 08:00:00');
        $service = Service::factory()->create(['duration_minutes' => 60]);

        Appointment::factory()->create([
            'appointment_date' => '2026-09-29',
            'starts_at' => '11:00:00',
            'ends_at' => '12:00:00',
        ]);

        $response = $this->from('/')->post('/appointments', [
            'customer_name' => 'Ana Perez',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '5551234567',
            'appointment_date' => '2026-09-29',
            'start_time' => '11:30',
            'service_ids' => [$service->id],
        ]);

        $response->assertRedirect('/')
            ->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('appointments', 1);
    }
}
