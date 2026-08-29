<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateBookingRequest
 *
 * Validación para actualizar una reserva.
 * Todos los campos usan 'sometimes': solo se validan si vienen.
 * El cambio de estado (cancelar) tiene su propia ruta, no se toca aquí.
 */
class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_id' => [
                'sometimes',
                'integer',
                Rule::exists('guests', 'id')->whereNull('deleted_at'),
            ],

            'room_id' => [
                'sometimes',
                'integer',
                Rule::exists('rooms', 'id')->whereNull('deleted_at'),
            ],

            'check_in_date' => ['sometimes', 'date', 'after_or_equal:today'],

            // 'after' aplica cuando ambas fechas viajan en la misma petición
            'check_out_date' => ['sometimes', 'date', 'after:check_in_date'],

            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.exists' => 'El huésped seleccionado no existe.',
            'room_id.exists' => 'La habitación seleccionada no existe.',
            'check_in_date.after_or_equal' => 'La fecha de entrada no puede ser en el pasado.',
            'check_out_date.after' => 'La fecha de salida debe ser posterior a la de entrada.',
        ];
    }
}
