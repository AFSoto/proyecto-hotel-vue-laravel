<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreBookingRequest
 *
 * Validación para crear una reserva.
 */
class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización por rol se maneja en las rutas (role:admin,receptionist)
        return true;
    }

    public function rules(): array
    {
        return [
            // El huésped debe existir y no estar eliminado
            'guest_id' => [
                'required',
                'integer',
                Rule::exists('guests', 'id')->whereNull('deleted_at'),
            ],

            // La habitación debe existir y no estar eliminada
            'room_id' => [
                'required',
                'integer',
                Rule::exists('rooms', 'id')->whereNull('deleted_at'),
            ],

            // No se puede reservar en el pasado
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],

            // La salida debe ser posterior a la entrada (mínimo 1 noche)
            'check_out_date' => ['required', 'date', 'after:check_in_date'],

            // Una reserva nueva solo puede nacer confirmada
            'status' => ['sometimes', 'in:confirmed'],

            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'guest_id.required' => 'El huésped es obligatorio.',
            'guest_id.exists' => 'El huésped seleccionado no existe.',
            'room_id.required' => 'La habitación es obligatoria.',
            'room_id.exists' => 'La habitación seleccionada no existe.',
            'check_in_date.required' => 'La fecha de entrada es obligatoria.',
            'check_in_date.after_or_equal' => 'La fecha de entrada no puede ser en el pasado.',
            'check_out_date.required' => 'La fecha de salida es obligatoria.',
            'check_out_date.after' => 'La fecha de salida debe ser posterior a la de entrada.',
            'status.in' => 'Una reserva nueva solo puede crearse en estado confirmado.',
        ];
    }
}
