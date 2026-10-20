<?php

namespace App\Http\Controllers\Admin;

use App\BusinessSchedule;
use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Models\BlockedTime;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function edit(BusinessSchedule $schedule): View
    {
        return view('admin.settings', [
            'currencies' => Currency::cases(),
            'current' => Currency::current(),
            'week' => $schedule->week(),
            'blockedTimes' => BlockedTime::query()
                ->whereDate('date', '>=', today())
                ->orderBy('date')
                ->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function update(UpdateCurrencyRequest $request): RedirectResponse
    {
        Setting::setValue(Currency::SETTING_KEY, $request->validated('currency'));

        return redirect()->route('admin.settings.edit')->with('success', 'Moneda actualizada.');
    }
}
