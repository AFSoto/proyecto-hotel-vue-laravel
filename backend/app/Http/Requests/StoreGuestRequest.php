<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreGuestRequest
 *
 * Validación para crear un huésped.
 */
class StoreGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización por rol se maneja en las rutas (role:admin,receptionist)
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],

            // Tipo de documento opcional; si no viene, el DTO usa 'cc'
            'document_type' => ['sometimes', 'string', 'max:20'],

            /**
             * Documento único SOLO entre los huéspedes vivos.
             * El Service se encarga de restaurar al huésped eliminado que
             * comparta documento, así que aquí ignoramos los soft-deleted.
             */
            'document_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('guests', 'document_number')->whereNull('deleted_at'),
            ],

            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'El nombre completo es obligatorio.',
            'full_name.max' => 'El nombre no puede exceder 150 caracteres.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.max' => 'El documento no puede exceder 30 caracteres.',
            'document_number.unique' => 'Ya existe un huésped con ese documento.',
            'email.email' => 'El formato del email no es válido.',
        ];
    }
}
