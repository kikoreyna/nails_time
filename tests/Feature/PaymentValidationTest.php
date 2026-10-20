<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentReviewStatus;
use App\Mail\AppointmentConfirmation;
use App\Mail\AppointmentRejected;
use App\Mail\PaymentProofSubmitted;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\PaymentProof;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PaymentValidationTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');
        $this->travelTo('2026-09-28 08:00:00');
        Technician::factory()->create();
        $this->service = Service::factory()->create(['duration_minutes' => 60, 'price' => 500]);
    }

    /** @param array<string, mixed> $overrides */
    private function book(array $overrides = [])
    {
        return $this->post('/appointments', [
            'customer_name' => 'Ana Perez',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '5551234567',
            'appointment_date' => '2026-09-29',
            'start_time' => '11:00',
            'service_ids' => [$this->service->id],
            ...$overrides,
        ]);
    }

    private function proofFile(string $name, int $padBytes = 0): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png.str_repeat("\0", $padBytes));
    }

    private function registerClient(string $email = 'ana@example.com'): Client
    {
        $client = Client::factory()->create(['email' => $email]);
        Appointment::factory()->create([
            'client_id' => $client->id,
            'appointment_date' => '2026-08-01',
            'status' => AppointmentStatus::Completed,
        ]);

        return $client;
    }

    private function pendingWithProof(): Appointment
    {
        $appointment = Appointment::factory()->pendingPayment(120)->create([
            'appointment_date' => '2026-09-29',
            'technician_id' => Technician::query()->value('id'),
        ]);
        PaymentProof::query()->create([
            'appointment_id' => $appointment->id,
            'image_path' => 'payment-proofs/proof.png',
            'uploaded_at' => now(),
        ]);

        return $appointment;
    }

    public function test_new_client_creates_pending_appointment_with_deposit_and_deadline(): void
    {
        $response = $this->book();

        $appointment = Appointment::query()->firstOrFail();
        $response->assertRedirect(URL::signedRoute('appointments.payment', $appointment));
        $this->assertSame(AppointmentStatus::PendingPaymentValidation, $appointment->status);
        $this->assertTrue($appointment->deposit_required);
        $this->assertSame('150.00', $appointment->deposit_amount);
        $this->assertTrue($appointment->expires_at->equalTo(now()->addMinutes(20)));
        Mail::assertNotSent(AppointmentConfirmation::class);
    }

    public function test_registered_client_is_confirmed_without_deposit(): void
    {
        $this->registerClient();

        $this->book()->assertRedirect(route('appointments.index'));

        $appointment = Appointment::query()->latest('id')->firstOrFail();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertFalse($appointment->deposit_required);
        Mail::assertSent(AppointmentConfirmation::class);
    }

    public function test_recent_no_show_or_blacklist_requires_deposit_again(): void
    {
        $client = $this->registerClient();
        Appointment::factory()->create([
            'client_id' => $client->id,
            'appointment_date' => '2026-09-01',
            'status' => AppointmentStatus::NoShow,
        ]);

        $this->book();

        $this->assertTrue(Appointment::query()->latest('id')->firstOrFail()->deposit_required);

        $this->registerClient('lola@example.com')->update(['is_blacklisted' => true]);
        $this->book(['customer_email' => 'lola@example.com', 'start_time' => '14:00']);

        $this->assertTrue(Appointment::query()->latest('id')->firstOrFail()->deposit_required);
    }

    public function test_pending_reservation_blocks_the_slot_until_it_expires(): void
    {
        $this->book();

        $this->book(['customer_email' => 'otra@example.com'])->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('appointments', 1);

        $this->travel(21)->minutes();
        $this->artisan('appointments:expire')->assertSuccessful();

        $this->assertSame(AppointmentStatus::Expired, Appointment::query()->firstOrFail()->status);
        $this->book(['customer_email' => 'otra@example.com'])->assertRedirect();
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_expired_but_unswept_reservation_does_not_block_the_slot(): void
    {
        $this->book();
        $this->travel(21)->minutes();

        $this->book(['customer_email' => 'otra@example.com'])->assertRedirect();

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_same_slot_is_assigned_to_different_technicians_until_all_are_busy(): void
    {
        Technician::factory()->create();

        $this->book();
        $this->book(['customer_email' => 'dos@example.com']);
        $this->book(['customer_email' => 'tres@example.com'])->assertSessionHasErrors('start_time');

        $this->assertSame(2, Appointment::query()->distinct()->count('technician_id'));
    }

    public function test_client_uploads_valid_proof_and_admin_is_emailed_with_attachment(): void
    {
        config(['booking.admin_email' => 'admin@example.com']);
        $this->book();
        $appointment = Appointment::query()->firstOrFail();

        $this->post(URL::signedRoute('appointments.proof.store', $appointment), [
            'proof' => $this->proofFile('comprobante.png'),
        ])->assertRedirect()->assertSessionHas('success');

        $proof = $appointment->paymentProof()->firstOrFail();
        Storage::disk('local')->assertExists($proof->image_path);
        $this->assertSame(PaymentReviewStatus::Pending, $proof->review_status);
        $this->assertSame(AppointmentStatus::PendingPaymentValidation, $appointment->fresh()->status);
        $this->assertTrue($appointment->fresh()->expires_at->equalTo(now()->addMinutes(180)));

        Mail::assertSent(PaymentProofSubmitted::class, function (PaymentProofSubmitted $mail): bool {
            $mail->assertSeeInHtml('Ana Perez')
                ->assertSeeInHtml('5551234567')
                ->assertSeeInHtml('$150.00');

            return $mail->hasTo('admin@example.com') && count($mail->attachments()) === 1;
        });
    }

    public function test_proof_upload_rejects_invalid_files(): void
    {
        $this->book();
        $appointment = Appointment::query()->firstOrFail();
        $url = URL::signedRoute('appointments.proof.store', $appointment);

        $this->post($url, ['proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('proof');
        $this->post($url, ['proof' => UploadedFile::fake()->create('proof.php', 10, 'text/x-php')])
            ->assertSessionHasErrors('proof');
        $this->post($url, ['proof' => $this->proofFile('big.png', 6 * 1024 * 1024)])
            ->assertSessionHasErrors('proof');
        $this->post($url, [])->assertSessionHasErrors('proof');

        $this->assertDatabaseCount('payment_proofs', 0);
        Mail::assertNotSent(PaymentProofSubmitted::class);
    }

    public function test_proof_cannot_be_uploaded_after_the_deadline_or_without_a_valid_signature(): void
    {
        $this->book();
        $appointment = Appointment::query()->firstOrFail();
        $signedUrl = URL::signedRoute('appointments.proof.store', $appointment);
        $file = $this->proofFile('proof.png');

        $this->post(route('appointments.proof.store', $appointment), ['proof' => $file])->assertForbidden();

        $this->travel(21)->minutes();
        $this->post($signedUrl, ['proof' => $file])->assertSessionHasErrors('proof');
        $this->assertDatabaseCount('payment_proofs', 0);
    }

    public function test_payment_page_requires_signed_url_and_shows_bank_data(): void
    {
        $this->book();
        $appointment = Appointment::query()->firstOrFail();

        $this->get(route('appointments.payment', $appointment))->assertForbidden();
        $this->get(URL::signedRoute('appointments.payment', $appointment))
            ->assertOk()
            ->assertSee('CLABE')
            ->assertSee('data-expires-at', false);
    }

    public function test_admin_approves_proof_and_client_is_notified(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = $this->pendingWithProof();

        $this->actingAs($admin)->post(route('admin.appointments.approve', $appointment))->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertNull($appointment->expires_at);
        $this->assertSame(PaymentReviewStatus::Approved, $appointment->paymentProof->review_status);
        $this->assertSame($admin->id, $appointment->paymentProof->reviewed_by_admin_id);
        Mail::assertSent(AppointmentConfirmation::class, fn ($mail): bool => $mail->hasTo($appointment->customer_email));
    }

    public function test_admin_rejects_proof_with_reason_and_slot_is_released(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = $this->pendingWithProof();

        $this->actingAs($admin)->post(route('admin.appointments.reject', $appointment))->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post(route('admin.appointments.reject', $appointment), ['reason' => 'Monto incorrecto'])
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Rejected, $appointment->status);
        $this->assertSame('Monto incorrecto', $appointment->paymentProof->rejection_reason);
        Mail::assertSent(AppointmentRejected::class, fn (AppointmentRejected $mail): bool => $mail->reason === 'Monto incorrecto');

        $this->book(['customer_email' => 'otra@example.com', 'start_time' => $appointment->starts_at])->assertRedirect();
    }

    public function test_admin_cannot_approve_after_the_review_window_expired(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = $this->pendingWithProof();
        $this->travel(121)->minutes();

        $this->actingAs($admin)->post(route('admin.appointments.approve', $appointment))->assertSessionHasErrors('appointment');

        $this->assertSame(AppointmentStatus::Expired, $appointment->fresh()->status);
    }

    public function test_admin_panel_is_restricted_to_admins(): void
    {
        $appointment = $this->pendingWithProof();

        $this->get(route('admin.appointments.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.appointments.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())->post(route('admin.appointments.approve', $appointment))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee('Comprobantes por validar');
    }

    public function test_admin_can_view_the_proof_image(): void
    {
        $appointment = $this->pendingWithProof();
        Storage::disk('local')->put('payment-proofs/proof.png', 'img');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.proofs.show', $appointment->paymentProof))
            ->assertOk();
    }
}
