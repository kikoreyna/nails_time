<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['Manicure esencial', 'Limpieza, limado y cuidado de cutícula.', 'Manos', 45, 350],
            ['Gel semipermanente', 'Color uniforme con acabado de larga duración.', 'Manos', 75, 520],
            ['Retiro de gel', 'Retiro cuidadoso del producto anterior.', 'Manos', 30, 180],
            ['Pedicure spa', 'Cuidado de uñas y exfoliación para los pies.', 'Pies', 60, 480],
            ['Acrílico escultural', 'Aplicación y forma personalizada.', 'Extensiones', 120, 850],
            ['Diseño de uñas', 'Detalle artístico por uña; agenda junto con otro servicio.', 'Diseño', 30, 150],
        ] as [$name, $description, $category, $duration, $price]) {
            Service::query()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'category' => $category,
                    'duration_minutes' => $duration,
                    'price' => $price,
                    'is_active' => true,
                ],
            );
        }
    }
}
