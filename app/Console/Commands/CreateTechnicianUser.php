<?php

namespace App\Console\Commands;

use App\Models\Technician;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('technician:create-user {email?} {--technician= : ID de la manicurista}')]
#[Description('Crea o actualiza el usuario con el que una manicurista ve su calendario')]
class CreateTechnicianUser extends Command
{
    public function handle(): int
    {
        $technicianId = $this->option('technician') ?: select(
            'Manicurista',
            Technician::query()->orderBy('name')->pluck('name', 'id')->all(),
        );
        $technician = Technician::query()->find($technicianId);

        if ($technician === null) {
            $this->error('La manicurista indicada no existe.');

            return self::FAILURE;
        }

        $email = $this->argument('email') ?? text('Correo de la manicurista', required: true);
        $password = password('Contraseña (mínimo 8 caracteres)', required: true, validate: fn (string $value): ?string => strlen($value) < 8 ? 'Mínimo 8 caracteres.' : null);

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $user->name ?? $technician->name,
            'password' => $password,
            'technician_id' => $technician->id,
        ])->save();

        $this->info("Usuario listo: {$user->email} ({$technician->name})");

        return self::SUCCESS;
    }
}
