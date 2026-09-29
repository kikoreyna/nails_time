<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Agenda tu cita | Nails Time</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="booking-page">
    <header class="topbar">
        <a class="brand" href="{{ route('appointments.index') }}" aria-label="Nails Time, inicio">
            <span class="brand-mark">nt</span>
            <span>Nails <strong>Time</strong></span>
        </a>
        <span class="topbar-note">ESTUDIO DE UÑAS <i></i> RESERVAS EN LÍNEA</span>
        <a class="topbar-contact" href="mailto:{{ config('mail.from.address') }}">¿Dudas? Escríbenos <span aria-hidden="true">↗</span></a>
    </header>

    <main>
        <section class="booking-intro">
            <div class="intro-copy">
                <p class="eyebrow"><span>01</span> TU MOMENTO, A TU MANERA</p>
                <h1>Agenda tu<br><em>cita</em> a tu ritmo.</h1>
                <p class="intro-description">Elige tus servicios, encuentra un horario libre y deja que nosotras nos encarguemos del resto.</p>
                <div class="intro-footnote"><span class="availability-dot"></span> Disponibilidad actualizada en tiempo real</div>
            </div>
            <div class="intro-art" aria-hidden="true">
                <div class="art-ring art-ring-one"></div>
                <div class="art-ring art-ring-two"></div>
                <div class="art-stamp">NAILS<br>TIME<span>✳</span></div>
                <div class="art-caption">TU PAUSA<br>FAVORITA</div>
                <span class="art-number">NT / 026</span>
            </div>
        </section>

        @if (session('success'))
            <div class="notice notice-success" role="status">{{ session('success') }}</div>
        @endif

        <form class="booking-form" method="POST" action="{{ route('appointments.store') }}" data-availability-url="{{ route('appointments.availability') }}" data-today="{{ $today }}">
            @csrf
            <section class="form-section service-section">
                <div class="section-heading">
                    <div><p class="eyebrow">PASO 01</p><h2>Selecciona tus servicios</h2></div>
                    <span class="section-aside">Elige uno o varios</span>
                </div>

                @error('service_ids')<p class="field-error">{{ $message }}</p>@enderror
                <div class="service-list">
                    @forelse ($services as $service)
                        <label class="service-option">
                            <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" data-duration="{{ $service->duration_minutes }}" data-price="{{ $service->price }}">
                            <span class="service-check" aria-hidden="true"></span>
                            <span class="service-main">
                                <span class="service-category">{{ $service->category }}</span>
                                <strong>{{ $service->name }}</strong>
                                <span class="service-description">{{ $service->description }}</span>
                            </span>
                            <span class="service-meta"><strong>${{ number_format((float) $service->price, 0) }}</strong><small>{{ $service->duration_minutes }} min</small></span>
                        </label>
                    @empty
                        <p class="empty-services">Estamos actualizando el catálogo. Escríbenos para agendar.</p>
                    @endforelse
                </div>
                <div class="selection-summary"><span id="selected-count">0 servicios seleccionados</span><strong id="selection-total">$0 · 0 min</strong></div>
            </section>

            <section class="form-section schedule-section">
                <div class="section-heading">
                    <div><p class="eyebrow">PASO 02</p><h2>Encuentra tu horario</h2></div>
                    <span class="section-aside">Los espacios cambian al reservar</span>
                </div>
                <div class="schedule-controls">
                    <label class="field date-field"><span>Fecha de tu cita</span><input type="date" name="appointment_date" min="{{ $today }}" value="{{ old('appointment_date') }}" required>@error('appointment_date')<small class="field-error">{{ $message }}</small>@enderror</label>
                    <div class="time-field"><span>Horarios disponibles</span><div class="time-grid" id="time-slots" aria-live="polite"><p class="slot-prompt">Primero elige servicios y fecha.</p></div>@error('start_time')<small class="field-error">{{ $message }}</small>@enderror</div>
                </div>
                <input type="hidden" name="start_time" id="start-time" value="{{ old('start_time') }}">
            </section>

            <section class="form-section details-section">
                <div class="section-heading">
                    <div><p class="eyebrow">PASO 03</p><h2>¿A quién esperamos?</h2></div>
                    <span class="section-aside">No necesitas crear una cuenta</span>
                </div>
                <div class="customer-fields">
                    <label class="field"><span>Nombre completo</span><input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Tu nombre" autocomplete="name" required>@error('customer_name')<small class="field-error">{{ $message }}</small>@enderror</label>
                    <label class="field"><span>Correo electrónico</span><input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="tu@correo.com" autocomplete="email" required>@error('customer_email')<small class="field-error">{{ $message }}</small>@enderror</label>
                    <label class="field"><span>Teléfono</span><input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="10 dígitos" autocomplete="tel" required>@error('customer_phone')<small class="field-error">{{ $message }}</small>@enderror</label>
                </div>
                <div class="submit-row"><p>Al confirmar recibirás un correo con los detalles de tu cita.</p><button type="submit" class="submit-button">Confirmar cita <span aria-hidden="true">↗</span></button></div>
            </section>
        </form>
    </main>

    <footer class="site-footer"><a class="brand" href="{{ route('appointments.index') }}"><span class="brand-mark">nt</span><span>Nails <strong>Time</strong></span></a><span>Un pequeño espacio para ti.</span><span>© {{ now()->year }} Nails Time</span></footer>
</body>
</html>