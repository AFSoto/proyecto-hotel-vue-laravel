<?php

namespace App\Repositories\Contracts;

/**
 * GuestRepositoryInterface
 *
 * Contrato del repositorio de huéspedes. Hereda el CRUD genérico
 * y añade una consulta de dominio.
 */
interface GuestRepositoryInterface extends RepositoryInterface
{
    /**
     * ¿El huésped tiene reservas activas (confirmed | checked_in)?
     * Se usa para impedir su borrado.
     */
    public function hasActiveBookings(int $id): bool;
}
