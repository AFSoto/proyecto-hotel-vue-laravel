<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;

/**
 * BookingRepositoryInterface
 *
 * Contrato del repositorio de reservas. Hereda el CRUD genérico
 * y declara las consultas propias del dominio (solapamiento, estado).
 */
interface BookingRepositoryInterface extends RepositoryInterface
{
    /**
     * ¿Existe otra reserva de la MISMA habitación que se solape con el rango?
     * Intervalo semiabierto [checkIn, checkOut). Excluye canceladas y, en update,
     * la propia reserva ($ignoreId).
     */
    public function hasOverlap(int $roomId, string $checkIn, string $checkOut, ?int $ignoreId = null): bool;

    /**
     * Cambiar el estado de una reserva.
     */
    public function changeStatus(int $id, string $status): Booking;

    /**
     * ¿La habitación tiene reservas activas (confirmed | checked_in)?
     * Se usa para impedir el borrado de la habitación.
     */
    public function roomHasActiveBookings(int $roomId): bool;
}
