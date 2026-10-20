<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>No pudimos validar tu comprobante</title></head>
<body style="margin:0;background:#f4f2ec;color:#172b26;font-family:Arial,sans-serif;padding:32px 16px;">
    <main style="max-width:560px;margin:0 auto;background:#fff;padding:36px;border-top:5px solid #d64e39;">
        <p style="font-size:12px;letter-spacing:2px;color:#d64e39;font-weight:bold;">NAILS TIME</p>
        <h1 style="font-size:26px;margin-bottom:8px;">No pudimos validar tu comprobante</h1>
        <p>Hola {{ $appointment->customer_name }}, tu cita del {{ $appointment->appointment_date->format('d/m/Y') }} a las {{ substr($appointment->starts_at, 0, 5) }} no quedó confirmada y el horario fue liberado.</p>
        <p style="border-left:4px solid #d64e39;padding:8px 14px;background:#faf6f3;"><strong>Motivo:</strong> {{ $reason }}</p>
        <p>Puedes agendar de nuevo cuando quieras en <a href="{{ route('appointments.index') }}">nuestra página de reservas</a>.</p>
        <p style="font-size:13px;color:#64716c;margin-top:28px;">Si crees que es un error, responde a este correo.</p>
    </main>
</body>
</html>
