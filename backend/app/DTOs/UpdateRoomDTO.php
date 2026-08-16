<?php

namespace App\DTOs;

/**
 * UpdateRoomDTO
 *
 * Transporta los datos validados para actualizar una habitación.
 * Todas las propiedades son nullable: en un update pueden no venir.
 */
class UpdateRoomDTO
{
    public function __construct(
        public readonly ?string $number,
        public readonly ?int $floor,
        public readonly ?int $roomTypeId,
        public readonly ?string $status,
        public readonly ?string $notes,
    ) {}

    /**
     * Construye el DTO desde el array validado del Request.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            number: $data['number'] ?? null,
            floor: isset($data['floor']) ? (int) $data['floor'] : null,
            roomTypeId: isset($data['room_type_id']) ? (int) $data['room_type_id'] : null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Devuelve solo los campos presentes (para no pisar columnas con null).
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->number !== null) {
            $data['number'] = $this->number;
        }
        if ($this->floor !== null) {
            $data['floor'] = $this->floor;
        }
        if ($this->roomTypeId !== null) {
            $data['room_type_id'] = $this->roomTypeId;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        if ($this->notes !== null) {
            $data['notes'] = $this->notes;
        }

        return $data;
    }
}
