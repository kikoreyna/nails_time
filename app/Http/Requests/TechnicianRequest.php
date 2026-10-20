<?php

namespace App\Http\Requests;

use App\Models\Technician;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TechnicianRequest extends FormRequest
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
        /** @var Technician|null $technician */
        $technician = $this->route('technician');
        $hasLogin = $technician?->user !== null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($technician?->user?->id)],
            'password' => [
                Rule::requiredIf(fn (): bool => filled($this->input('email')) && ! $hasLogin),
                'nullable',
                'string',
                'min:8',
                'max:255',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre de la manicurista.',
            'email.unique' => 'Ese correo ya está en uso por otro usuario.',
            'password.required' => 'Define una contraseña para crear su acceso.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
