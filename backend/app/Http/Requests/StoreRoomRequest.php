<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreRoomRequest
 *
 * Validación para crear una habitación.
 */
class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización por rol se maneja en las rutas (middleware role:admin)
        return true;
    }

    public function rules(): array
    {
        return [
            // Número único, máx 10 caracteres
            'number' => ['required', 'string', 'max:10', 'unique:rooms,number'],

            // Piso: entero 0–255 (unsignedTinyInteger)
            'floor' => ['required', 'integer', 'min:0', 'max:255'],

            // Debe existir el tipo de habitación
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],

            // Estado opcional; si no viene, el DTO usa 'available'
            'status' => ['sometimes', 'in:available,occupied,maintenance'],

            // Notas opcionales
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'number.required' => 'El número de habitación es obligatorio.',
            'number.max' => 'El número no puede exceder 10 caracteres.',
            'number.unique' => 'Ya existe una habitación con ese número.',
            'floor.required' => 'El piso es obligatorio.',
            'floor.integer' => 'El piso debe ser un número entero.',
            'floor.min' => 'El piso no puede ser negativo.',
            'floor.max' => 'El piso no puede ser mayor a 255.',
            'room_type_id.required' => 'El tipo de habitación es obligatorio.',
            'room_type_id.exists' => 'El tipo de habitación seleccionado no existe.',
            'status.in' => 'El estado debe ser: available, occupied o maintenance.',
        ];
    }
}
