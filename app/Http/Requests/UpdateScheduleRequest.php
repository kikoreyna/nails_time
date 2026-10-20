<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'days' => ['required', 'array'],
            'days.*.is_open' => ['nullable', 'boolean'],
            'days.*.opens_at' => ['required', 'date_format:H:i'],
            'days.*.closes_at' => ['required', 'date_format:H:i'],
            'days.*.break_starts_at' => ['nullable', 'date_format:H:i', 'required_with:days.*.break_ends_at'],
            'days.*.break_ends_at' => ['nullable', 'date_format:H:i', 'required_with:days.*.break_starts_at'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ((array) $this->input('days', []) as $weekday => $day) {
                $opens = $day['opens_at'] ?? null;
                $closes = $day['closes_at'] ?? null;
                $breakStart = $day['break_starts_at'] ?? null;
                $breakEnd = $day['break_ends_at'] ?? null;

                if (! in_array((int) $weekday, range(1, 7), true)) {
                    $validator->errors()->add('days', 'Día inválido.');

                    continue;
                }

                if ($opens && $closes && $opens >= $closes) {
                    $validator->errors()->add("days.$weekday.closes_at", 'El cierre debe ser posterior a la apertura.');
                }

                if ($breakStart && $breakEnd && ($breakStart >= $breakEnd || $breakStart <= $opens || $breakEnd >= $closes)) {
                    $validator->errors()->add("days.$weekday.break_starts_at", 'La pausa debe quedar dentro del horario y terminar después de empezar.');
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.*.opens_at.*' => 'Indica una hora de apertura válida.',
            'days.*.closes_at.*' => 'Indica una hora de cierre válida.',
            'days.*.break_starts_at.*' => 'Completa el inicio y el fin de la pausa, o deja ambos vacíos.',
            'days.*.break_ends_at.*' => 'Completa el inicio y el fin de la pausa, o deja ambos vacíos.',
        ];
    }
}
