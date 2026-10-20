<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlockedTimeRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'starts_at' => ['nullable', 'date_format:H:i', 'required_with:ends_at'],
            'ends_at' => ['nullable', 'date_format:H:i', 'required_with:starts_at', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Elige la fecha a bloquear.',
            'date.after_or_equal' => 'La fecha no puede estar en el pasado.',
            'starts_at.required_with' => 'Indica la hora de inicio y fin, o deja ambas vacías para bloquear todo el día.',
            'ends_at.required_with' => 'Indica la hora de inicio y fin, o deja ambas vacías para bloquear todo el día.',
            'ends_at.after' => 'La hora final debe ser posterior a la inicial.',
        ];
    }
}
