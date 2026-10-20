<x-admin-layout title="Configuración">
    <div class="panel-page">
        <div class="admin-bar">
            <div><p class="eyebrow">ADMINISTRACIÓN</p><h1>Configuración</h1></div>
        </div>

        @if (session('success'))
            <div class="notice notice-success" role="status">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="panel-card" style="display:grid;gap:18px;max-width:560px;">
            @csrf
            @method('PUT')

            <label class="field">
                <span>Moneda del sistema</span>
                <select name="currency" required>
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency->value }}" @selected(old('currency', $current->value) === $currency->value)>{{ $currency->label() }}</option>
                    @endforeach
                </select>
                @error('currency')<small class="field-error">{{ $message }}</small>@enderror
            </label>

            <p style="margin:0;font-size:13px;color:var(--muted);">Define cómo se muestran los precios y depósitos en el sitio, el panel y los correos. No convierte montos: los precios de los servicios se interpretan en la moneda elegida.</p>

            <div class="calendar-nav-links">
                <button type="submit" class="submit-button">Guardar</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.schedule.update') }}" class="panel-card" style="display:grid;gap:14px;margin-top:24px;">
            @csrf
            @method('PUT')
            <h2 style="margin:0;font-size:18px;">Horario de atención</h2>
            <p style="margin:0;font-size:13px;color:var(--muted);">Define qué días y a qué horas se puede agendar. Para horario corrido deja la pausa vacía; si cierras a comer, indica el inicio y el fin de la pausa.</p>
            @error('days')<small class="field-error">{{ $message }}</small>@enderror

            <table class="admin-table">
                <thead><tr><th>Día</th><th>Abierto</th><th>Apertura</th><th>Cierre</th><th>Pausa desde</th><th>Pausa hasta</th></tr></thead>
                <tbody>
                @foreach (['1' => 'Lunes', '2' => 'Martes', '3' => 'Miércoles', '4' => 'Jueves', '5' => 'Viernes', '6' => 'Sábado', '7' => 'Domingo'] as $weekday => $label)
                    @php $day = $week[$weekday]; @endphp
                    <tr>
                        <td><strong>{{ $label }}</strong></td>
                        <td><input type="checkbox" name="days[{{ $weekday }}][is_open]" value="1" @checked(old("days.$weekday.is_open", old('days') ? false : $day['is_open'])) aria-label="{{ $label }} abierto"></td>
                        <td><input type="time" name="days[{{ $weekday }}][opens_at]" value="{{ old("days.$weekday.opens_at", $day['opens_at']) }}" required></td>
                        <td><input type="time" name="days[{{ $weekday }}][closes_at]" value="{{ old("days.$weekday.closes_at", $day['closes_at']) }}" required>@error("days.$weekday.closes_at")<small class="field-error">{{ $message }}</small>@enderror</td>
                        <td><input type="time" name="days[{{ $weekday }}][break_starts_at]" value="{{ old("days.$weekday.break_starts_at", $day['break_starts_at']) }}">@error("days.$weekday.break_starts_at")<small class="field-error">{{ $message }}</small>@enderror</td>
                        <td><input type="time" name="days[{{ $weekday }}][break_ends_at]" value="{{ old("days.$weekday.break_ends_at", $day['break_ends_at']) }}"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="calendar-nav-links">
                <button type="submit" class="submit-button">Guardar horario</button>
            </div>
        </form>

        <section class="panel-card" style="display:grid;gap:14px;margin-top:24px;">
            <h2 style="margin:0;font-size:18px;">Fechas y horas bloqueadas</h2>
            <p style="margin:0;font-size:13px;color:var(--muted);">Bloquea un día completo (festivos, vacaciones) o un rango de horas. Deja las horas vacías para bloquear todo el día.</p>

            <form method="POST" action="{{ route('admin.blocked-times.store') }}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                @csrf
                <label class="field"><span>Fecha</span><input type="date" name="date" min="{{ today()->toDateString() }}" value="{{ old('date') }}" required>@error('date')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Desde (opcional)</span><input type="time" name="starts_at" value="{{ old('starts_at') }}">@error('starts_at')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Hasta (opcional)</span><input type="time" name="ends_at" value="{{ old('ends_at') }}">@error('ends_at')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Motivo</span><input type="text" name="reason" value="{{ old('reason') }}" maxlength="120"></label>
                <button type="submit" class="submit-button">Bloquear</button>
            </form>

            <table class="admin-table">
                <thead><tr><th>Fecha</th><th>Horas</th><th>Motivo</th><th></th></tr></thead>
                <tbody>
                @forelse ($blockedTimes as $block)
                    <tr>
                        <td>{{ $block->date->format('d/m/Y') }}</td>
                        <td>{{ $block->starts_at ? substr($block->starts_at, 0, 5).' – '.substr($block->ends_at, 0, 5) : 'Todo el día' }}</td>
                        <td>{{ $block->reason ?: '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.blocked-times.destroy', $block) }}" onsubmit="return confirm('¿Quitar este bloqueo?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="icon-button is-danger" title="Eliminar" aria-label="Eliminar bloqueo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No hay bloqueos próximos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-admin-layout>
