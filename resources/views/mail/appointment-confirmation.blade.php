<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Tu cita está confirmada</title></head>
<body style="margin:0;background:#f4f2ec;color:#172b26;font-family:Arial,sans-serif;padding:32px 16px;">
    <main style="max-width:560px;margin:0 auto;background:#fff;padding:36px;border-top:5px solid #d64e39;">
        <p style="font-size:12px;letter-spacing:2px;color:#d64e39;font-weight:bold;">NAILS TIME</p>
        <h1 style="font-size:28px;margin-bottom:8px;">Tu cita está confirmada</h1>
        <p>Hola {{ $appointment->customer_name }}, te esperamos el {{ $appointment->appointment_date->format('d/m/Y') }} a las {{ substr($appointment->starts_at, 0, 5) }}.</p>
        <h2 style="font-size:16px;margin-top:28px;">Tus servicios</h2>
        <ul style="padding-left:20px;">
            @foreach ($appointment->services as $service)
                <li style="padding:5px 0;">{{ $service->pivot->service_name }} · {{ $service->pivot->duration_minutes }} min</li>
            @endforeach
        </ul>
        <p style="border-top:1px solid #e5e7e2;padding-top:18px;"><strong>Duración:</strong> {{ $appointment->total_minutes }} min<br><strong>Total:</strong> ${{ number_format((float) $appointment->total_price, 2) }}</p>
        <p style="font-size:13px;color:#64716c;margin-top:28px;">Si necesitas cambiar tu cita, responde a este correo.</p>
    </main>
</body>
</html>