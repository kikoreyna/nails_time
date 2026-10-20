<?php

namespace Database\Seeders;

use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ServiceSeeder::class);

        foreach (['Ana', 'Valeria'] as $name) {
            Technician::query()->firstOrCreate(['name' => $name]);
        }

        if (env('ADMIN_EMAIL') && env('ADMIN_PASSWORD')) {
            $admin = User::query()->firstOrNew(['email' => env('ADMIN_EMAIL')]);
            $admin->forceFill([
                'name' => $admin->name ?? 'Administración',
                'password' => env('ADMIN_PASSWORD'),
                'is_admin' => true,
            ])->save();
        }

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
