<?php

namespace App\DTOs;

/**
 * CreateBookingDTO
 *
 * Transporta los datos validados para crear una reserva.
 * OJO: user_id y total_price NO viven aquí; los pone el Service
 * (user_id del autenticado, total_price congelado del cálculo).
 */
class CreateBookingDTO
{
    public function __construct(
        public readonly int $guestId,
        public readonly int $roomId,
        public readonly string $checkInDate,
        public readonly string $checkOutDate,
        public readonly string $status,
        public readonly ?string $notes,
    ) {}

    /**
     * Construye el DTO desde el array validado del Request.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            guestId: (int) $data['guest_id'],
            roomId: (int) $data['room_id'],
            checkInDate: $data['check_in_date'],
            checkOutDate: $data['check_out_date'],
            // Una reserva nueva nace confirmada salvo que se indique otra cosa
            status: $data['status'] ?? 'confirmed',
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Mapea a las columnas de la tabla bookings (user_id y total_price los añade el Service).
     */
    public function toArray(): array
    {
        return [
            'guest_id' => $this->guestId,
            'room_id' => $this->roomId,
            'check_in_date' => $this->checkInDate,
            'check_out_date' => $this->checkOutDate,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
