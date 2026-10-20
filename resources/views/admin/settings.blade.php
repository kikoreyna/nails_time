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
    </div>
</x-admin-layout>
