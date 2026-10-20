<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Models\Appointment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencySettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_currency_defaults_to_mxn(): void
    {
        $this->assertSame(Currency::Mxn, Currency::current());
        $this->assertSame('$1,500.00 MXN', Currency::current()->format(1500));
    }

    public function test_admin_changes_currency(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk()->assertSee('Moneda del sistema');

        $this->actingAs($admin)->put(route('admin.settings.update'), ['currency' => 'USD'])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame(Currency::Usd, Currency::current());
        $this->assertSame('$20.00 USD', Currency::current()->format(20));
    }

    public function test_invalid_currency_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update'), ['currency' => 'EUR'])
            ->assertSessionHasErrors('currency');

        $this->assertNull(Setting::getValue(Currency::SETTING_KEY));
    }

    public function test_non_admin_cannot_change_currency(): void
    {
        $this->put(route('admin.settings.update'), ['currency' => 'USD'])->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.update'), ['currency' => 'USD'])
            ->assertForbidden();
    }

    public function test_public_page_and_admin_list_use_selected_currency(): void
    {
        Setting::setValue(Currency::SETTING_KEY, 'USD');
        $appointment = Appointment::factory()->pendingPayment()->create();

        $this->get(route('appointments.index'))->assertOk()->assertSee('USD');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee('$150.00 USD', false);

        $this->assertNotNull($appointment);
    }
}
