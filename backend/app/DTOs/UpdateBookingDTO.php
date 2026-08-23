<?php

namespace App\DTOs;

/**
 * UpdateBookingDTO
 *
 * Datos validados para actualizar una reserva. Todas las propiedades son
 * nullable: en un update pueden no venir. toArray() devuelve solo lo presente.
 */
class UpdateBookingDTO
{
    public function __construct(
        public readonly ?int $guestId,
        public readonly ?int $roomId,
        public readonly ?string $checkInDate,
        public readonly ?string $checkOutDate,
        public readonly ?string $status,
        public readonly ?string $notes,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            guestId: isset($data['guest_id']) ? (int) $data['guest_id'] : null,
            roomId: isset($data['room_id']) ? (int) $data['room_id'] : null,
            checkInDate: $data['check_in_date'] ?? null,
            checkOutDate: $data['check_out_date'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Devuelve solo los campos presentes (update parcial).
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->guestId !== null) {
            $data['guest_id'] = $this->guestId;
        }
        if ($this->roomId !== null) {
            $data['room_id'] = $this->roomId;
        }
        if ($this->checkInDate !== null) {
            $data['check_in_date'] = $this->checkInDate;
        }
        if ($this->checkOutDate !== null) {
            $data['check_out_date'] = $this->checkOutDate;
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
