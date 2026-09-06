<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * BookingResource
 *
 * Transforma el modelo Booking a la estructura JSON pública de la API.
 */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Fechas como YYYY-MM-DD (sin hora): son columnas date
            'check_in_date' => $this->check_in_date->toDateString(),
            'check_out_date' => $this->check_out_date->toDateString(),

            'status' => $this->status,
            'total_price' => $this->total_price,
            'notes' => $this->notes,

            // Marcas de tiempo reales de los movimientos (null si aún no ocurren)
            'checked_in_at' => $this->checked_in_at?->toISOString(),
            'checked_out_at' => $this->checked_out_at?->toISOString(),

            // IDs planos siempre disponibles
            'guest_id' => $this->guest_id,
            'room_id' => $this->room_id,
            'user_id' => $this->user_id,

            /**
             * Relaciones anidadas: solo si fueron cargadas (whenLoaded),
             * evitando consultas N+1 accidentales.
             */
            'guest' => new GuestResource($this->whenLoaded('guest')),
            'room' => new RoomResource($this->whenLoaded('room')),

            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
