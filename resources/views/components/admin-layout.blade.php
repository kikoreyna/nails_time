@props(['title'])

@php
    $user = auth()->user();
    $pendingReviews = $user->is_admin
        ? \App\Models\Appointment::query()
            ->where('status', \App\Enums\AppointmentStatus::PendingPaymentValidation)
            ->whereHas('paymentProof')
            ->count()
        : 0;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Nails Time</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="booking-page">
    <div class="admin-shell">
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="admin-sidebar-head">
                <a class="brand" href="{{ route('admin.calendar') }}" aria-label="Nails Time, panel">
                    <span class="brand-mark">nt</span>
                    <span>Nails <strong>Time</strong></span>
                </a>
                <button type="button" class="admin-sidebar-toggle" aria-controls="admin-sidebar-nav" aria-expanded="false" data-sidebar-toggle>Menú</button>
            </div>

            <nav class="admin-sidebar-nav" id="admin-sidebar-nav" aria-label="Panel">
                <p class="admin-sidebar-label">{{ $user->is_admin ? 'ADMINISTRACIÓN' : 'MI AGENDA' }}</p>
                <a href="{{ route('admin.calendar') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.calendar')])>
                    <span aria-hidden="true">▦</span> Calendario
                </a>
                @if ($user->is_admin)
                    <a href="{{ route('admin.appointments.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.appointments.index')])>
                        <span aria-hidden="true">✓</span> Comprobantes
                        @if ($pendingReviews > 0)
                            <b class="admin-nav-badge" aria-label="{{ $pendingReviews }} pendientes">{{ $pendingReviews }}</b>
                        @endif
                    </a>
                @endif
                @if ($user->is_admin)
                    <a href="{{ route('admin.technicians.index') }}" @class(['admin-nav-link', 'is-active' => request()->routeIs('admin.technicians.*')])>
                        <span aria-hidden="true">☺</span> Manicuristas
                    </a>
                @endif
                <a href="{{ route('appointments.index') }}" class="admin-nav-link" target="_blank" rel="noopener">
                    <span aria-hidden="true">↗</span> Ver sitio de reservas
                </a>
            </nav>

            <div class="admin-sidebar-foot">
                <p><strong>{{ $user->name }}</strong><small>{{ $user->is_admin ? 'Administrador' : ($user->technician?->name ?? 'Manicurista') }}</small></p>
                <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit" class="admin-logout">Cerrar sesión</button></form>
            </div>
        </aside>

        <main class="admin-content">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
