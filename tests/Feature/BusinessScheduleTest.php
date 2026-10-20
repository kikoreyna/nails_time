<?php

namespace Tests\Feature;

use App\BusinessSchedule;
use App\Models\BlockedTime;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BusinessScheduleTest extends TestCase
{
    use RefreshDatabase;

    private string $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monday = Carbon::now(config('booking.timezone'))->next(Carbon::MONDAY)->addWeek()->toDateString();
        Technician::factory()->create();
    }

    /**
     * @return array<int, string>
     */
    private function slotTimes(?string $date = null): array
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);

        return collect($this->getJson(route('appointments.availability', [
            'date' => $date ?? $this->monday,
            'services' => [$service->id],
        ]))->assertOk()->json('slots'))->pluck('time')->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function week(array $monday = []): array
    {
        $days = [];

        foreach (range(1, 7) as $weekday) {
            $days[$weekday] = ['is_open' => 1, 'opens_at' => '09:00', 'closes_at' => '18:00'];
        }

        $days[1] = [...$days[1], ...$monday];

        return $days;
    }

    public function test_default_schedule_is_continuous(): void
    {
        $times = $this->slotTimes();

        $this->assertContains('09:00', $times);
        $this->assertContains('13:00', $times);
        $this->assertContains('17:00', $times);
        $this->assertNotContains('17:30', $times);
    }

    public function test_admin_saves_schedule_with_break(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.schedule.update'), [
            'days' => $this->week(['opens_at' => '10:00', 'closes_at' => '19:00', 'break_starts_at' => '14:00', 'break_ends_at' => '15:00']),
        ])->assertRedirect(route('admin.settings.edit'));

        $times = $this->slotTimes();

        $this->assertNotContains('09:00', $times);
        $this->assertContains('10:00', $times);
        $this->assertContains('13:00', $times);
        $this->assertNotContains('13:30', $times);
        $this->assertNotContains('14:30', $times);
        $this->assertContains('15:00', $times);
        $this->assertContains('18:00', $times);
    }

    public function test_closed_day_has_no_slots(): void
    {
        $admin = User::factory()->admin()->create();
        $days = $this->week();
        unset($days[1]['is_open']);

        $this->actingAs($admin)->put(route('admin.schedule.update'), ['days' => $days])->assertRedirect();

        $this->assertSame([], $this->slotTimes());
    }

    public function test_invalid_schedule_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.schedule.update'), [
            'days' => $this->week(['opens_at' => '18:00', 'closes_at' => '09:00']),
        ])->assertSessionHasErrors('days.1.closes_at');

        $this->actingAs($admin)->put(route('admin.schedule.update'), [
            'days' => $this->week(['break_starts_at' => '14:00']),
        ])->assertSessionHasErrors();
    }

    public function test_blocked_hours_and_full_days(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.blocked-times.store'), [
            'date' => $this->monday, 'starts_at' => '12:00', 'ends_at' => '14:00', 'reason' => 'Comida',
        ])->assertRedirect(route('admin.settings.edit'));

        $times = $this->slotTimes();
        $this->assertContains('11:00', $times);
        $this->assertNotContains('11:30', $times);
        $this->assertNotContains('13:00', $times);
        $this->assertContains('14:00', $times);

        $nextDay = Carbon::parse($this->monday)->addDay()->toDateString();
        $this->actingAs($admin)->post(route('admin.blocked-times.store'), ['date' => $nextDay])->assertRedirect();
        $this->assertSame([], $this->slotTimes($nextDay));
    }

    public function test_blocked_slot_cannot_be_booked_directly(): void
    {
        BlockedTime::query()->create(['date' => $this->monday, 'starts_at' => '10:00', 'ends_at' => '11:00']);
        $service = Service::factory()->create(['duration_minutes' => 60]);

        $this->post(route('appointments.store'), [
            'service_ids' => [$service->id],
            'appointment_date' => $this->monday,
            'start_time' => '10:00',
            'customer_name' => 'Ana',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '5555555555',
        ])->assertSessionHasErrors('start_time');
    }

    public function test_block_can_be_removed_and_requires_admin(): void
    {
        $block = BlockedTime::query()->create(['date' => $this->monday]);

        $this->delete(route('admin.blocked-times.destroy', $block))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->delete(route('admin.blocked-times.destroy', $block))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.blocked-times.destroy', $block))->assertRedirect();
        $this->assertModelMissing($block);
        $this->assertTrue((new BusinessSchedule)->windows($this->monday) !== []);
    }
}
