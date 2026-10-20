<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Comprobante por validar</title></head>
<body style="margin:0;background:#f4f2ec;color:#172b26;font-family:Arial,sans-serif;padding:32px 16px;">
    <main style="max-width:560px;margin:0 auto;background:#fff;padding:36px;border-top:5px solid #d64e39;">
        <p style="font-size:12px;letter-spacing:2px;color:#d64e39;font-weight:bold;">NAILS TIME · ADMINISTRACIÓN</p>
        <h1 style="font-size:26px;margin-bottom:8px;">Nuevo comprobante por validar</h1>
        <p>El comprobante viene adjunto a este correo. Revísalo antes de las {{ $appointment->expires_at?->timezone(config('booking.timezone'))->format('d/m/Y H:i') }}; después la cita expira y el horario se libera.</p>

        <table role="presentation" width="100%" cellpadding="6" style="border-collapse:collapse;font-size:14px;margin-top:18px;">
            <tr><td style="color:#64716c;">Cliente</td><td><strong>{{ $appointment->customer_name }}</strong></td></tr>
            <tr><td style="color:#64716c;">Teléfono</td><td>{{ $appointment->customer_phone }}</td></tr>
            <tr><td style="color:#64716c;">Correo</td><td>{{ $appointment->customer_email }}</td></tr>
            <tr><td style="color:#64716c;">Fecha y hora</td><td>{{ $appointment->appointment_date->format('d/m/Y') }} · {{ substr($appointment->starts_at, 0, 5) }} – {{ substr($appointment->ends_at, 0, 5) }}</td></tr>
            <tr><td style="color:#64716c;">Monto total</td><td>{{ \App\Enums\Currency::current()->format($appointment->total_price) }}</td></tr>
            <tr><td style="color:#64716c;">Depósito esperado</td><td><strong>{{ \App\Enums\Currency::current()->format($appointment->deposit_amount) }}</strong></td></tr>
        </table>

        <h2 style="font-size:16px;margin-top:24px;">Servicios</h2>
        <ul style="padding-left:20px;">
            @foreach ($appointment->services as $service)
                <li style="padding:4px 0;">{{ $service->pivot->service_name }} · {{ $service->pivot->duration_minutes }} min</li>
            @endforeach
        </ul>

        <p style="margin-top:28px;"><a href="{{ route('admin.appointments.index') }}" style="display:inline-block;background:#d64e39;color:#fff;padding:13px 22px;text-decoration:none;font-weight:bold;">Revisar en el panel</a></p>
        <p style="font-size:12px;color:#64716c;margin-top:24px;">Subido el {{ $proof->uploaded_at->timezone(config('booking.timezone'))->format('d/m/Y H:i') }}.</p>
    </main>
</body>
</html>
