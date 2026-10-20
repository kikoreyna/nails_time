<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'currencies' => Currency::cases(),
            'current' => Currency::current(),
        ]);
    }

    public function update(UpdateCurrencyRequest $request): RedirectResponse
    {
        Setting::setValue(Currency::SETTING_KEY, $request->validated('currency'));

        return redirect()->route('admin.settings.edit')->with('success', 'Moneda actualizada.');
    }
}
