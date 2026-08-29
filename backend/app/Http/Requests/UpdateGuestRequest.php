<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateGuestRequest
 *
 * Validación para actualizar un huésped.
 * Todos los campos usan 'sometimes': solo se validan si vienen.
 */
class UpdateGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // ID del huésped desde la ruta (guests/{id})
        $guestId = $this->route('id');

        return [
            'full_name' => ['sometimes', 'string', 'max:150'],

            'document_type' => ['sometimes', 'string', 'max:20'],

            // Único entre huéspedes vivos, ignorando el propio registro
            'document_number' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('guests', 'document_number')
                    ->whereNull('deleted_at')
                    ->ignore($guestId),
            ],

            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.max' => 'El nombre no puede exceder 150 caracteres.',
            'document_number.max' => 'El documento no puede exceder 30 caracteres.',
            'document_number.unique' => 'Ya existe un huésped con ese documento.',
            'email.email' => 'El formato del email no es válido.',
        ];
    }
}
