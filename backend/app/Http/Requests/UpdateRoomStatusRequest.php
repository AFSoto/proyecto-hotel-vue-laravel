<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateRoomStatusRequest
 *
 * Validación para el cambio de estado de una habitación
 * (endpoint PATCH rooms/{id}/status, usado por recepción).
 */
class UpdateRoomStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:available,occupied,maintenance'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser: available, occupied o maintenance.',
        ];
    }
}
