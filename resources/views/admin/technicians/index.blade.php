<x-admin-layout title="Manicuristas">
    <div class="panel-page">
        <div class="admin-bar">
            <div><p class="eyebrow">ADMINISTRACIÓN</p><h1>Manicuristas</h1></div>
            <a href="{{ route('admin.technicians.create') }}" class="submit-button" style="text-decoration:none;">Nueva manicurista</a>
        </div>

        @if (session('success'))
            <div class="notice notice-success" role="status">{{ session('success') }}</div>
        @endif
        @error('technician')<div class="notice" role="alert">{{ $message }}</div>@enderror

        <section class="panel-card">
            <table class="admin-table">
                <thead><tr><th>Nombre</th><th>Estado</th><th>Acceso al panel</th><th>Citas</th><th>Acciones</th></tr></thead>
                <tbody>
                @forelse ($technicians as $technician)
                    <tr>
                        <td><strong>{{ $technician->name }}</strong></td>
                        <td><span class="status-badge">{{ $technician->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                        <td>{{ $technician->user?->email ?? 'Sin acceso' }}</td>
                        <td>{{ $technician->appointments_count }}</td>
                        <td>
                            <div class="icon-actions">
                                <a href="{{ route('admin.technicians.edit', $technician) }}" class="icon-button" title="Editar" aria-label="Editar {{ $technician->name }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.technicians.destroy', $technician) }}" onsubmit="return confirm('¿Eliminar a {{ $technician->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-button is-danger" title="Eliminar" aria-label="Eliminar {{ $technician->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Aún no hay manicuristas. Agrega la primera.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-admin-layout>
