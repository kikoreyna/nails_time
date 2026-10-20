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
                            <div class="admin-actions">
                                <a href="{{ route('admin.technicians.edit', $technician) }}" class="secondary-button" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:var(--ink);">Editar</a>
                                <form method="POST" action="{{ route('admin.technicians.destroy', $technician) }}" onsubmit="return confirm('¿Eliminar a {{ $technician->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="secondary-button">Eliminar</button>
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
