<x-admin-layout :title="$technician->exists ? 'Editar manicurista' : 'Nueva manicurista'">
    <div class="panel-page">
        <div class="admin-bar">
            <div><p class="eyebrow">MANICURISTAS</p><h1>{{ $technician->exists ? 'Editar '.$technician->name : 'Nueva manicurista' }}</h1></div>
        </div>

        <form method="POST" action="{{ $technician->exists ? route('admin.technicians.update', $technician) : route('admin.technicians.store') }}" class="panel-card" style="display:grid;gap:18px;max-width:560px;">
            @csrf
            @if ($technician->exists) @method('PUT') @endif

            <label class="field"><span>Nombre</span><input type="text" name="name" value="{{ old('name', $technician->name) }}" maxlength="120" required>@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>

            <label style="display:flex;align-items:center;gap:10px;font-size:14px;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $technician->is_active))>
                Activa (recibe reservas y puede entrar al panel)
            </label>

            <fieldset style="display:grid;gap:14px;border:1px solid var(--line);padding:16px;">
                <legend style="padding:0 8px;font-size:13px;font-weight:700;">Acceso al panel (opcional)</legend>
                <p style="margin:0;font-size:13px;color:var(--muted);">Con un correo y contraseña podrá ver su propio calendario. Déjalo vacío si no necesita acceso.</p>
                <label class="field"><span>Correo</span><input type="email" name="email" value="{{ old('email', $technician->user?->email) }}" autocomplete="off">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>{{ $technician->user ? 'Nueva contraseña (vacío para conservar la actual)' : 'Contraseña' }}</span><input type="password" name="password" autocomplete="new-password">@error('password')<small class="field-error">{{ $message }}</small>@enderror</label>
            </fieldset>

            <div class="calendar-nav-links">
                <button type="submit" class="submit-button">Guardar</button>
                <a href="{{ route('admin.technicians.index') }}" class="secondary-button" style="display:inline-flex;align-items:center;text-decoration:none;color:var(--ink);">Cancelar</a>
            </div>
        </form>
    </div>
</x-admin-layout>
