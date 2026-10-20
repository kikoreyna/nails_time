<x-admin-layout :title="$service->exists ? 'Editar servicio' : 'Nuevo servicio'">
    <div class="panel-page">
        <div class="admin-bar">
            <div><p class="eyebrow">SERVICIOS</p><h1>{{ $service->exists ? 'Editar '.$service->name : 'Nuevo servicio' }}</h1></div>
        </div>

        <form method="POST" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" class="panel-card" style="display:grid;gap:18px;max-width:560px;">
            @csrf
            @if ($service->exists) @method('PUT') @endif

            <label class="field"><span>Nombre</span><input type="text" name="name" value="{{ old('name', $service->name) }}" maxlength="120" required>@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Categoría</span><input type="text" name="category" value="{{ old('category', $service->category) }}" maxlength="80" list="service-categories" placeholder="Manos, Pies, Extensiones…" required>@error('category')<small class="field-error">{{ $message }}</small>@enderror</label>
            <datalist id="service-categories">
                @foreach (\App\Models\Service::query()->distinct()->orderBy('category')->pluck('category') as $category)
                    <option value="{{ $category }}"></option>
                @endforeach
            </datalist>
            <label class="field"><span>Descripción</span><textarea name="description" rows="3" maxlength="500">{{ old('description', $service->description) }}</textarea>@error('description')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Duración (minutos)</span><input type="number" name="duration_minutes" value="{{ old('duration_minutes', $service->duration_minutes) }}" min="5" max="480" step="5" required>@error('duration_minutes')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Precio ({{ $currency->value }})</span><input type="number" name="price" value="{{ old('price', $service->price) }}" min="0" step="0.01" required>@error('price')<small class="field-error">{{ $message }}</small>@enderror</label>

            <label style="display:flex;align-items:center;gap:10px;font-size:14px;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active))>
                Activo (visible para reservar)
            </label>

            <div class="calendar-nav-links">
                <button type="submit" class="submit-button">Guardar</button>
                <a href="{{ route('admin.services.index') }}" class="secondary-button" style="display:inline-flex;align-items:center;text-decoration:none;color:var(--ink);">Cancelar</a>
            </div>
        </form>
    </div>
</x-admin-layout>
