<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * RoomResource
 *
 * Transforma el modelo Room a la estructura JSON pública de la API.
 */
class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'floor' => $this->floor,
            'status' => $this->status,
            'notes' => $this->notes,
            'room_type_id' => $this->room_type_id,

            /**
             * Tipo de habitación anidado.
             * Solo se incluye si la relación fue cargada (whenLoaded),
             * evitando consultas N+1 accidentales.
             */
            'room_type' => new RoomTypeResource($this->whenLoaded('roomType')),

            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
