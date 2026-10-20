<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffCalendarTest extends TestCase
{
    use RefreshDatabase;

    private Technician $ana;

    private Technician $valeria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-28 08:00:00');
        $this->ana = Technician::factory()->create(['name' => 'Ana']);
        $this->valeria = Technician::factory()->create(['name' => 'Valeria']);

        $this->appointmentFor($this->ana, 'Clienta de Ana');
        $this->appointmentFor($this->valeria, 'Clienta de Valeria');
        $this->appointmentFor($this->ana, 'Clienta expirada', ['status' => AppointmentStatus::Expired]);
        $this->appointmentFor($this->ana, 'Clienta lejana', ['appointment_date' => '2026-12-01']);
    }

    /** @param array<string, mixed> $overrides */
    private function appointmentFor(Technician $technician, string $name, array $overrides = []): Appointment
    {
        return Appointment::factory()->create([
            'technician_id' => $technician->id,
            'customer_name' => $name,
            'appointment_date' => '2026-09-29',
            ...$overrides,
        ]);
    }

    public function test_admin_sees_every_technician_and_can_filter_by_one(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.calendar'))
            ->assertOk()
            ->assertSee('Clienta de Ana')
            ->assertSee('Clienta de Valeria')
            ->assertDontSee('Clienta expirada')
            ->assertDontSee('Clienta lejana');

        $this->actingAs($admin)->get(route('admin.calendar', ['technician_id' => $this->valeria->id]))
            ->assertSee('Clienta de Valeria')
            ->assertDontSee('Clienta de Ana');
    }

    public function test_technician_only_sees_her_own_appointments_even_when_filtering_by_another(): void
    {
        $user = User::factory()->create(['technician_id' => $this->ana->id]);

        $this->actingAs($user)->get(route('admin.calendar', ['technician_id' => $this->valeria->id]))
            ->assertOk()
            ->assertSee('Clienta de Ana')
            ->assertDontSee('Clienta de Valeria');
    }

    public function test_calendar_navigates_between_weeks(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.calendar', ['date' => '2026-12-02']))
            ->assertSee('Clienta lejana')
            ->assertDontSee('Clienta de Ana');
    }

    public function test_calendar_is_restricted_to_staff_and_technicians_cannot_review_payments(): void
    {
        $technician = User::factory()->create(['technician_id' => $this->ana->id]);

        $this->get(route('admin.calendar'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.calendar'))->assertForbidden();
        $this->actingAs($technician)->get(route('admin.appointments.index'))->assertForbidden();
    }

    public function test_technician_can_log_in_and_lands_on_the_calendar(): void
    {
        User::factory()->create(['email' => 'ana@salon.test', 'technician_id' => $this->ana->id]);

        $this->post(route('admin.login.store'), ['email' => 'ana@salon.test', 'password' => 'password'])
            ->assertRedirect(route('admin.calendar'));
    }

    public function test_staff_can_close_a_started_confirmed_appointment_and_it_counts_toward_registration(): void
    {
        $user = User::factory()->create(['technician_id' => $this->ana->id]);
        $appointment = Appointment::query()->where('customer_name', 'Clienta de Ana')->firstOrFail();

        $this->actingAs($user)->post(route('admin.appointments.status', $appointment), ['status' => 'completed'])
            ->assertSessionHasErrors('status');

        $this->travelTo('2026-09-29 12:00:00');
        $this->actingAs($user)->post(route('admin.appointments.status', $appointment), ['status' => 'completed'])
            ->assertSessionHas('success');

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
    }

    public function test_technician_cannot_close_another_technicians_appointment_or_use_invalid_status(): void
    {
        $this->travelTo('2026-09-29 12:00:00');
        $user = User::factory()->create(['technician_id' => $this->ana->id]);
        $other = Appointment::query()->where('customer_name', 'Clienta de Valeria')->firstOrFail();
        $own = Appointment::query()->where('customer_name', 'Clienta de Ana')->firstOrFail();

        $this->actingAs($user)->post(route('admin.appointments.status', $other), ['status' => 'no_show'])->assertForbidden();
        $this->actingAs($user)->post(route('admin.appointments.status', $own), ['status' => 'confirmed'])
            ->assertSessionHasErrors('status');
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.appointments.status', $other), ['status' => 'no_show'])
            ->assertSessionHas('success');

        $this->assertSame(AppointmentStatus::NoShow, $other->fresh()->status);
    }

    public function test_regular_users_cannot_log_in_to_the_panel(): void
    {
        User::factory()->create(['email' => 'cliente@salon.test']);

        $this->post(route('admin.login.store'), ['email' => 'cliente@salon.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
