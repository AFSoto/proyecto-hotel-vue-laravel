<?php

namespace App\DTOs;

/**
 * CreateRoomDTO
 *
 * Transporta los datos ya validados para crear una habitación.
 */
class CreateRoomDTO
{
    public function __construct(
        public readonly string $number,
        public readonly int $floor,
        public readonly int $roomTypeId,
        public readonly string $status,
        public readonly ?string $notes,
    ) {}

    /**
     * Construye el DTO desde el array validado del Request.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            number: $data['number'],
            floor: (int) $data['floor'],
            roomTypeId: (int) $data['room_type_id'],
            // Estado por defecto si no se envía
            status: $data['status'] ?? 'available',
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Mapea a las columnas de la tabla rooms.
     */
    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'floor' => $this->floor,
            'room_type_id' => $this->roomTypeId,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
