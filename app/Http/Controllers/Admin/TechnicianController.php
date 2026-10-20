<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TechnicianRequest;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TechnicianController extends Controller
{
    public function index(): View
    {
        return view('admin.technicians.index', [
            'technicians' => Technician::query()
                ->with('user')
                ->withCount('appointments')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.technicians.form', ['technician' => new Technician(['is_active' => true])]);
    }

    public function store(TechnicianRequest $request): RedirectResponse
    {
        $technician = DB::transaction(function () use ($request): Technician {
            $technician = Technician::query()->create([
                'name' => $request->validated('name'),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncLogin($technician, $request);

            return $technician;
        });

        return redirect()->route('admin.technicians.index')->with('success', "{$technician->name} fue agregada.");
    }

    public function edit(Technician $technician): View
    {
        return view('admin.technicians.form', ['technician' => $technician->load('user')]);
    }

    public function update(TechnicianRequest $request, Technician $technician): RedirectResponse
    {
        DB::transaction(function () use ($request, $technician): void {
            $technician->update([
                'name' => $request->validated('name'),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncLogin($technician, $request);
        });

        return redirect()->route('admin.technicians.index')->with('success', 'Manicurista actualizada.');
    }

    public function destroy(Technician $technician): RedirectResponse
    {
        if ($technician->appointments()->exists()) {
            return back()->withErrors([
                'technician' => 'No se puede eliminar a una manicurista con citas registradas. Desactívala para que deje de recibir reservas.',
            ]);
        }

        DB::transaction(function () use ($technician): void {
            $technician->user()->where('is_admin', false)->delete();
            $technician->delete();
        });

        return redirect()->route('admin.technicians.index')->with('success', 'Manicurista eliminada.');
    }

    private function syncLogin(Technician $technician, TechnicianRequest $request): void
    {
        $email = $request->validated('email');

        if (blank($email)) {
            return;
        }

        $user = $technician->user ?? new User;
        $user->forceFill([
            'name' => $technician->name,
            'email' => $email,
            'technician_id' => $technician->id,
        ]);

        if (filled($request->validated('password'))) {
            $user->password = $request->validated('password');
        }

        $user->save();
    }
}
