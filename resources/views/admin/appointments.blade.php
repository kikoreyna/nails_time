<x-admin-layout title="Comprobantes">
    <div class="panel-page">
        <div class="admin-bar"><div><p class="eyebrow">ADMINISTRACIÓN</p><h1>Comprobantes por validar</h1></div></div>

        @if (session('success'))
            <div class="notice notice-success" role="status">{{ session('success') }}</div>
        @endif
        @error('appointment')<div class="notice" role="alert">{{ $message }}</div>@enderror
        @error('reason')<div class="notice" role="alert">{{ $message }}</div>@enderror

        <section class="panel-card">
            <h2>Pendientes ({{ $pending->count() }})</h2>
            <table class="admin-table">
                <thead><tr><th>Cliente</th><th>Cita</th><th>Depósito</th><th>Vence</th><th>Comprobante</th><th>Acción</th></tr></thead>
                <tbody>
                @forelse ($pending as $appointment)
                    <tr>
                        <td><strong>{{ $appointment->customer_name }}</strong><br>{{ $appointment->customer_phone }}<br>{{ $appointment->customer_email }}</td>
                        <td>{{ $appointment->appointment_date->format('d/m/Y') }} {{ substr($appointment->starts_at, 0, 5) }}<br>{{ $appointment->services->pluck('pivot.service_name')->join(', ') }}<br>{{ $appointment->technician?->name }}</td>
                        <td>${{ number_format((float) $appointment->deposit_amount, 2) }} de ${{ number_format((float) $appointment->total_price, 2) }}</td>
                        <td>{{ $appointment->expires_at?->timezone(config('booking.timezone'))->format('d/m H:i') }}</td>
                        <td>
                            @if ($appointment->paymentProof)
                                <a href="{{ route('admin.proofs.show', $appointment->paymentProof) }}" target="_blank" rel="noopener">Ver imagen</a>
                            @else
                                Aún no sube
                            @endif
                        </td>
                        <td>
                            @if ($appointment->paymentProof)
                                <div class="admin-actions">
                                    <form method="POST" action="{{ route('admin.appointments.approve', $appointment) }}">@csrf<button type="submit" class="submit-button">Aprobar</button></form>
                                    <form method="POST" action="{{ route('admin.appointments.reject', $appointment) }}">@csrf
                                        <label class="field"><span>Motivo del rechazo</span><textarea name="reason" rows="2" maxlength="500" required></textarea></label>
                                        <button type="submit" class="secondary-button">Rechazar</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No hay comprobantes pendientes.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <section class="panel-card">
            <h2>Últimas citas</h2>
            <table class="admin-table">
                <thead><tr><th>Cliente</th><th>Cita</th><th>Estado</th></tr></thead>
                <tbody>
                @foreach ($recent as $appointment)
                    <tr>
                        <td>{{ $appointment->customer_name }}</td>
                        <td>{{ $appointment->appointment_date->format('d/m/Y') }} {{ substr($appointment->starts_at, 0, 5) }}</td>
                        <td><span class="status-badge">{{ $appointment->status->label() }}</span>@if ($appointment->paymentProof?->rejection_reason)<br><small>{{ $appointment->paymentProof->rejection_reason }}</small>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
    </div>
</x-admin-layout>
