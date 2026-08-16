<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateRoomRequest
 *
 * Validación para actualizar una habitación.
 * Todos los campos usan 'sometimes': solo se validan si vienen.
 */
class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // ID de la habitación desde la ruta (rooms/{id})
        $roomId = $this->route('id');

        return [
            // Único ignorando el propio registro
            'number' => [
                'sometimes',
                'string',
                'max:10',
                Rule::unique('rooms', 'number')->ignore($roomId),
            ],

            'floor' => ['sometimes', 'integer', 'min:0', 'max:255'],

            'room_type_id' => ['sometimes', 'integer', 'exists:room_types,id'],

            'status' => ['sometimes', 'in:available,occupied,maintenance'],

            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'number.max' => 'El número no puede exceder 10 caracteres.',
            'number.unique' => 'Ya existe una habitación con ese número.',
            'floor.integer' => 'El piso debe ser un número entero.',
            'floor.min' => 'El piso no puede ser negativo.',
            'floor.max' => 'El piso no puede ser mayor a 255.',
            'room_type_id.exists' => 'El tipo de habitación seleccionado no existe.',
            'status.in' => 'El estado debe ser: available, occupied o maintenance.',
        ];
    }
}
