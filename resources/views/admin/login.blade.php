<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso administración | Nails Time</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="booking-page">
    <main class="panel-page" style="max-width:420px;">
        <p class="eyebrow">NAILS TIME</p>
        <h1>Administración</h1>
        <form method="POST" action="{{ route('admin.login.store') }}" class="panel-card" style="display:grid;gap:16px;">
            @csrf
            <label class="field"><span>Correo</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required>@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>Contraseña</span><input type="password" name="password" autocomplete="current-password" required></label>
            <button type="submit" class="submit-button">Entrar</button>
        </form>
    </main>
</body>
</html>
