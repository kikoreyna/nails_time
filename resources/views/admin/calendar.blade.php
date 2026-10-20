<x-admin-layout title="Calendario">
    <div class="panel-page calendar-page">
        <div class="admin-bar">
            <div>
                <p class="eyebrow">{{ auth()->user()->is_admin ? 'ADMINISTRACIÓN' : 'MI AGENDA' }}</p>
                <h1>Calendario{{ $ownTechnician ? ' · '.$ownTechnician->name : '' }}</h1>
            </div>
        </div>

        @if (session('success'))
            <div class="notice notice-success" role="status">{{ session('success') }}</div>
        @endif
        @error('status')<div class="notice" role="alert">{{ $message }}</div>@enderror

        <div class="calendar-toolbar">
            <div class="calendar-week-nav">
                <a class="secondary-button" href="{{ route('admin.calendar', array_filter(['date' => $previousWeek, 'technician_id' => $technicianId])) }}" aria-label="Semana anterior">←</a>
                <strong>{{ $weekStart->locale('es')->isoFormat('D MMM') }} – {{ $weekEnd->locale('es')->isoFormat('D MMM YYYY') }}</strong>
                <a class="secondary-button" href="{{ route('admin.calendar', array_filter(['date' => $nextWeek, 'technician_id' => $technicianId])) }}" aria-label="Semana siguiente">→</a>
                <a class="secondary-button" href="{{ route('admin.calendar', array_filter(['technician_id' => $technicianId])) }}">Hoy</a>
            </div>

            @if (auth()->user()->is_admin)
                <form method="GET" action="{{ route('admin.calendar') }}" class="calendar-filter">
                    <input type="hidden" name="date" value="{{ $weekStart->toDateString() }}">
                    <label class="field"><span>Manicurista</span>
                        <select name="technician_id" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            @foreach ($technicians as $technician)
                                <option value="{{ $technician->id }}" @selected($technicianId == $technician->id)>{{ $technician->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </form>
            @endif
        </div>

        <div class="calendar-grid">
            @foreach ($days as $day)
                @php($dayAppointments = $appointments->get($day->toDateString(), collect()))
                <section class="calendar-day {{ $day->toDateString() === $today ? 'is-today' : '' }}">
                    <header>
                        <span>{{ $day->locale('es')->isoFormat('ddd') }}</span>
                        <strong>{{ $day->format('d') }}</strong>
                    </header>

                    @forelse ($dayAppointments as $appointment)
                        <article class="calendar-appointment status-{{ $appointment->status->value }}">
                            <strong>{{ substr($appointment->starts_at, 0, 5) }} – {{ substr($appointment->ends_at, 0, 5) }}</strong>
                            <span>{{ $appointment->customer_name }}</span>
                            <small>{{ $appointment->services->pluck('pivot.service_name')->join(', ') }}</small>
                            @if (auth()->user()->is_admin)
                                <small>{{ $appointment->technician?->name ?? 'Sin asignar' }}</small>
                            @endif
                            <small>{{ $appointment->customer_phone }}</small>
                            <span class="status-badge">{{ $appointment->status->label() }}</span>
                            @if ($appointment->status === \App\Enums\AppointmentStatus::Confirmed && \Illuminate\Support\Carbon::parse($appointment->appointment_date->toDateString().' '.$appointment->starts_at, config('booking.timezone'))->isPast())
                                <form method="POST" action="{{ route('admin.appointments.status', $appointment) }}" class="calendar-close-actions">
                                    @csrf
                                    <button type="submit" name="status" value="completed" class="secondary-button">Completada</button>
                                    <button type="submit" name="status" value="no_show" class="secondary-button">No asistió</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="calendar-empty">Sin citas</p>
                    @endforelse
                </section>
            @endforeach
        </div>
    </div>
</x-admin-layout>
