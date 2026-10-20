<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TechnicianCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_lists_technicians(): void
    {
        Technician::factory()->create(['name' => 'Valeria']);

        $this->actingAs($this->admin)->get(route('admin.technicians.index'))
            ->assertOk()
            ->assertSee('Valeria');
    }

    public function test_admin_creates_a_technician_without_login(): void
    {
        $this->actingAs($this->admin)->post(route('admin.technicians.store'), ['name' => 'Ana', 'is_active' => '1'])
            ->assertRedirect(route('admin.technicians.index'));

        $technician = Technician::query()->firstOrFail();
        $this->assertSame('Ana', $technician->name);
        $this->assertTrue($technician->is_active);
        $this->assertNull($technician->user);
    }

    public function test_admin_creates_a_technician_with_a_working_login(): void
    {
        $this->actingAs($this->admin)->post(route('admin.technicians.store'), [
            'name' => 'Ana',
            'is_active' => '1',
            'email' => 'ana@salon.test',
            'password' => 'secreto123',
        ])->assertRedirect(route('admin.technicians.index'));

        $user = User::query()->where('email', 'ana@salon.test')->firstOrFail();
        $technician = Technician::query()->firstOrFail();
        $this->assertSame($technician->id, $user->technician_id);
        $this->assertFalse($user->is_admin);

        auth()->logout();
        $this->post(route('admin.login.store'), ['email' => 'ana@salon.test', 'password' => 'secreto123'])
            ->assertRedirect(route('admin.calendar'));
    }

    public function test_validation_rules_for_name_email_and_password(): void
    {
        User::factory()->create(['email' => 'usado@salon.test']);

        $this->actingAs($this->admin)->post(route('admin.technicians.store'), [])
            ->assertSessionHasErrors('name');
        $this->post(route('admin.technicians.store'), ['name' => 'Ana', 'email' => 'ana@salon.test'])
            ->assertSessionHasErrors('password');
        $this->post(route('admin.technicians.store'), ['name' => 'Ana', 'email' => 'usado@salon.test', 'password' => 'secreto123'])
            ->assertSessionHasErrors('email');
        $this->post(route('admin.technicians.store'), ['name' => 'Ana', 'email' => 'ana@salon.test', 'password' => 'corta'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('technicians', 0);
    }

    public function test_admin_updates_name_status_and_keeps_password_when_left_blank(): void
    {
        $technician = Technician::factory()->create(['name' => 'Ana']);
        $user = User::factory()->create(['email' => 'ana@salon.test', 'technician_id' => $technician->id]);
        $originalHash = $user->password;

        $this->actingAs($this->admin)->put(route('admin.technicians.update', $technician), [
            'name' => 'Ana María',
            'is_active' => '0',
            'email' => 'nuevo@salon.test',
            'password' => '',
        ])->assertRedirect(route('admin.technicians.index'));

        $technician->refresh();
        $user->refresh();
        $this->assertSame('Ana María', $technician->name);
        $this->assertFalse($technician->is_active);
        $this->assertSame('nuevo@salon.test', $user->email);
        $this->assertSame($originalHash, $user->password);
    }

    public function test_admin_can_change_a_technician_password(): void
    {
        $technician = Technician::factory()->create();
        $user = User::factory()->create(['technician_id' => $technician->id]);

        $this->actingAs($this->admin)->put(route('admin.technicians.update', $technician), [
            'name' => $technician->name,
            'is_active' => '1',
            'email' => $user->email,
            'password' => 'otraclave123',
        ]);

        $this->assertTrue(Hash::check('otraclave123', $user->fresh()->password));
    }

    public function test_inactive_technician_cannot_access_the_panel(): void
    {
        $technician = Technician::factory()->create(['is_active' => false]);
        $user = User::factory()->create(['email' => 'ana@salon.test', 'technician_id' => $technician->id]);

        $this->post(route('admin.login.store'), ['email' => 'ana@salon.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->actingAs($user)->get(route('admin.calendar'))->assertForbidden();
    }

    public function test_technician_with_appointments_cannot_be_deleted(): void
    {
        $technician = Technician::factory()->create();
        Appointment::factory()->create(['technician_id' => $technician->id]);

        $this->actingAs($this->admin)->delete(route('admin.technicians.destroy', $technician))
            ->assertSessionHasErrors('technician');

        $this->assertModelExists($technician);
    }

    public function test_technician_without_appointments_is_deleted_with_her_login(): void
    {
        $technician = Technician::factory()->create();
        $user = User::factory()->create(['technician_id' => $technician->id]);

        $this->actingAs($this->admin)->delete(route('admin.technicians.destroy', $technician))
            ->assertRedirect(route('admin.technicians.index'));

        $this->assertModelMissing($technician);
        $this->assertModelMissing($user);
    }

    public function test_only_admins_can_manage_technicians(): void
    {
        $technician = Technician::factory()->create();
        $staff = User::factory()->create(['technician_id' => $technician->id]);

        $this->get(route('admin.technicians.index'))->assertRedirect(route('login'));
        $this->actingAs($staff)->get(route('admin.technicians.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.technicians.store'), ['name' => 'X'])->assertForbidden();
        $this->actingAs($staff)->delete(route('admin.technicians.destroy', $technician))->assertForbidden();
    }
}
