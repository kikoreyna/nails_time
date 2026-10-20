<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $extensions = implode(',', config('booking.proof.mimes'));

        return [
            'proof' => [
                'required',
                'file',
                'mimes:'.$extensions,
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.config('booking.proof.max_kilobytes'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proof.required' => 'Adjunta la captura de tu comprobante.',
            'proof.file' => 'El comprobante debe ser un archivo válido.',
            'proof.uploaded' => 'No pudimos subir el archivo. Revisa que no pese más de '.(int) (config('booking.proof.max_kilobytes') / 1024).' MB.',
            'proof.mimes' => 'El comprobante debe ser una imagen JPG, PNG o WEBP.',
            'proof.mimetypes' => 'El comprobante debe ser una imagen JPG, PNG o WEBP.',
            'proof.max' => 'El comprobante no puede pesar más de '.(int) (config('booking.proof.max_kilobytes') / 1024).' MB.',
        ];
    }
}
