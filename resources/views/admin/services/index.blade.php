<x-admin-layout title="Servicios">
    <div class="panel-page">
        <div class="admin-bar">
            <div><p class="eyebrow">ADMINISTRACIÓN</p><h1>Servicios</h1></div>
            <a href="{{ route('admin.services.create') }}" class="submit-button" style="text-decoration:none;">Nuevo servicio</a>
        </div>

        @if (session('success'))
            <div class="notice notice-success" role="status">{{ session('success') }}</div>
        @endif
        @error('service')<div class="notice" role="alert">{{ $message }}</div>@enderror

        <section class="panel-card">
            <table class="admin-table">
                <thead><tr><th>Servicio</th><th>Categoría</th><th>Duración</th><th>Precio</th><th>Estado</th><th>Citas</th><th>Acciones</th></tr></thead>
                <tbody>
                @forelse ($services as $service)
                    <tr>
                        <td><strong>{{ $service->name }}</strong></td>
                        <td>{{ $service->category }}</td>
                        <td>{{ $service->duration_minutes }} min</td>
                        <td>{{ $currency->format($service->price) }}</td>
                        <td><span class="status-badge">{{ $service->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                        <td>{{ $service->appointments_count }}</td>
                        <td>
                            <div class="icon-actions">
                                <a href="{{ route('admin.services.edit', $service) }}" class="icon-button" title="Editar" aria-label="Editar {{ $service->name }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.services.destroy', $service) }}" onsubmit="return confirm('¿Eliminar {{ $service->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-button is-danger" title="Eliminar" aria-label="Eliminar {{ $service->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Aún no hay servicios. Agrega el primero.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-admin-layout>
