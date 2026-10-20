<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Gelish',
            'category' => 'Manos',
            'description' => 'Color de larga duración',
            'duration_minutes' => 60,
            'price' => 450.5,
            'is_active' => 1,
            ...$overrides,
        ];
    }

    public function test_admin_lists_services(): void
    {
        Service::factory()->create(['name' => 'Acrílico']);

        $this->actingAs($this->admin)->get(route('admin.services.index'))
            ->assertOk()->assertSee('Acrílico');
    }

    public function test_admin_creates_service(): void
    {
        $this->actingAs($this->admin)->post(route('admin.services.store'), $this->payload())
            ->assertRedirect(route('admin.services.index'));

        $this->assertDatabaseHas('services', ['name' => 'Gelish', 'duration_minutes' => 60, 'is_active' => true]);
    }

    public function test_admin_edits_and_deactivates_service(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.services.edit', $service))->assertOk();

        $this->actingAs($this->admin)->put(route('admin.services.update', $service), $this->payload([
            'name' => 'Press-on',
            'is_active' => 0,
        ]))->assertRedirect(route('admin.services.index'));

        $service->refresh();
        $this->assertSame('Press-on', $service->name);
        $this->assertFalse($service->is_active);
    }

    public function test_validation_rejects_bad_data(): void
    {
        $this->actingAs($this->admin)->post(route('admin.services.store'), $this->payload([
            'name' => '',
            'duration_minutes' => 0,
            'price' => -5,
        ]))->assertSessionHasErrors(['name', 'duration_minutes', 'price']);
    }

    public function test_service_without_appointments_can_be_deleted(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.services.destroy', $service))
            ->assertRedirect(route('admin.services.index'));

        $this->assertModelMissing($service);
    }

    public function test_service_with_appointments_cannot_be_deleted(): void
    {
        $service = Service::factory()->create();
        $appointment = Appointment::factory()->create();
        $appointment->services()->attach($service->id, ['service_name' => $service->name, 'duration_minutes' => 60, 'price' => 100]);

        $this->actingAs($this->admin)->delete(route('admin.services.destroy', $service))
            ->assertSessionHasErrors('service');

        $this->assertModelExists($service);
    }

    public function test_only_admin_can_manage_services(): void
    {
        $this->get(route('admin.services.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.services.index'))
            ->assertForbidden();
    }
}
