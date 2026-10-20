<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('admin:create {email?} {--name=Administración}')]
#[Description('Crea o actualiza un usuario administrador')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email') ?? text('Correo del administrador', required: true);
        $password = password('Contraseña (mínimo 8 caracteres)', required: true, validate: fn (string $value): ?string => strlen($value) < 8 ? 'Mínimo 8 caracteres.' : null);

        $admin = User::query()->firstOrNew(['email' => $email]);
        $admin->forceFill([
            'name' => $admin->name ?? $this->option('name'),
            'password' => $password,
            'is_admin' => true,
        ])->save();

        $this->info("Administrador listo: {$admin->email}");

        return self::SUCCESS;
    }
}
