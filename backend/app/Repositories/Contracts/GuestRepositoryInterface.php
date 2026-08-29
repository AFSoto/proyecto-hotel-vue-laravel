<?php

namespace App\Repositories\Contracts;

use App\Models\Guest;

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

    /**
     * Buscar un huésped ELIMINADO (soft delete) por su documento.
     * Se usa para restaurarlo en vez de duplicar cuando un huésped regresa.
     */
    public function findTrashedByDocument(string $documentNumber): ?Guest;
}
