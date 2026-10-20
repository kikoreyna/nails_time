<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Credenciales incorrectas.']);
        }

        $user = $request->user();

        if (! $user->is_admin && $user->technician_id === null) {
            Auth::logout();

            return back()->withInput($request->only('email'))->withErrors(['email' => 'Credenciales incorrectas.']);
        }

        $request->session()->regenerate();

        return redirect()->intended($user->is_admin ? route('admin.appointments.index') : route('admin.calendar'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
