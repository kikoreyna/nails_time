<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services.index', [
            'services' => Service::query()
                ->withCount('appointments')
                ->orderByDesc('is_active')
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
            'currency' => Currency::current(),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.form', [
            'service' => new Service(['is_active' => true, 'duration_minutes' => 60]),
            'currency' => Currency::current(),
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = Service::query()->create($this->attributes($request));

        return redirect()->route('admin.services.index')->with('success', "{$service->name} fue agregado.");
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', ['service' => $service, 'currency' => Currency::current()]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($this->attributes($request));

        return redirect()->route('admin.services.index')->with('success', 'Servicio actualizado.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        if ($service->appointments()->exists()) {
            return back()->withErrors([
                'service' => 'No se puede eliminar un servicio con citas registradas. Desactívalo para que deje de ofrecerse.',
            ]);
        }

        $service->delete();

        return redirect()->route('admin.services.index')->with('success', 'Servicio eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ServiceRequest $request): array
    {
        return [
            ...$request->safe()->except('is_active'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
