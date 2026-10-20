<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tu depósito | Nails Time</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="booking-page">
    <header class="topbar">
        <a class="brand" href="{{ route('appointments.index') }}"><span class="brand-mark">nt</span><span>Nails <strong>Time</strong></span></a>
    </header>

    <main class="panel-page">
        <p class="eyebrow">TU RESERVA</p>
        <h1>{{ $appointment->appointment_date->format('d/m/Y') }} · {{ substr($appointment->starts_at, 0, 5) }}</h1>
        <p>{{ $appointment->services->pluck('pivot.service_name')->join(', ') }} · {{ $appointment->total_minutes }} min · Total {{ \App\Enums\Currency::current()->format($appointment->total_price) }}</p>

        @if (session('success'))
            <div class="notice notice-success" role="status">{{ session('success') }}</div>
        @endif

        @if ($canUpload)
            <div class="countdown" data-expires-at="{{ $appointment->expires_at->toIso8601String() }}">
                <span>Tu horario está apartado por</span><strong data-countdown>--:--</strong>
            </div>

            <section class="panel-card">
                <h2>1. Transfiere tu depósito de {{ \App\Enums\Currency::current()->format($appointment->deposit_amount) }}</h2>
                <dl class="bank-data">
                    <div><dt>Banco</dt><dd>{{ $bank['bank_name'] }}</dd></div>
                    <div><dt>Titular</dt><dd>{{ $bank['account_holder'] }}</dd></div>
                    <div><dt>CLABE (SPEI)</dt><dd>{{ $bank['clabe'] }}</dd></div>
                    @if ($bank['oxxo_card'])
                        <div><dt>Tarjeta para depósito en OXXO</dt><dd>{{ $bank['oxxo_card'] }}</dd></div>
                    @endif
                    <div><dt>Concepto</dt><dd>Cita #{{ $appointment->id }} · {{ $appointment->customer_name }}</dd></div>
                </dl>
            </section>

            <section class="panel-card">
                <h2>2. Sube la captura de tu comprobante</h2>
                <form method="POST" action="{{ $uploadUrl }}" enctype="multipart/form-data">
                    @csrf
                    <label class="field"><span>Imagen (JPG, PNG o WEBP, máx. {{ (int) (config('booking.proof.max_kilobytes') / 1024) }} MB)</span><input type="file" name="proof" accept="image/jpeg,image/png,image/webp" required>@error('proof')<small class="field-error">{{ $message }}</small>@enderror</label>
                    <div class="submit-row"><p>Validaremos tu pago y te avisaremos por correo.</p><button type="submit" class="submit-button">Enviar comprobante <span aria-hidden="true">↗</span></button></div>
                </form>
            </section>
        @else
            <section class="panel-card">
                <h2>Estado: <span class="status-badge">{{ $appointment->status->label() }}</span></h2>
                @switch($appointment->status)
                    @case(\App\Enums\AppointmentStatus::PendingPaymentValidation)
                        <p>Estamos revisando tu comprobante. Te avisaremos por correo en cuanto lo validemos.</p>
                        @break
                    @case(\App\Enums\AppointmentStatus::Confirmed)
                        <p>¡Tu cita está confirmada! Te esperamos.</p>
                        @break
                    @case(\App\Enums\AppointmentStatus::Rejected)
                        <p>No pudimos validar tu comprobante y el horario fue liberado.</p>
                        @break
                    @default
                        <p>El tiempo para apartar este horario terminó y fue liberado. Puedes <a href="{{ route('appointments.index') }}">agendar de nuevo</a>.</p>
                @endswitch
                @error('proof')<p class="field-error">{{ $message }}</p>@enderror
            </section>
        @endif
    </main>
</body>
</html>
