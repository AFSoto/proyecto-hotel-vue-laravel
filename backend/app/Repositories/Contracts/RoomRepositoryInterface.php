<?php

namespace App\Repositories\Contracts;

use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;

/**
 * RoomRepositoryInterface
 *
 * Contrato del repositorio para el modelo Room.
 *
 * Extiende RepositoryInterface para heredar el CRUD básico
 * (create, update, delete, findByIdOrFail, paginate, etc.)
 * y añade los métodos de consulta propios del dominio de habitaciones.
 */
interface RoomRepositoryInterface extends RepositoryInterface
{
    /**
     * Habitaciones disponibles (sin reserva que solape) en un rango de fechas.
     * Excluye las que están en mantenimiento y, opcionalmente, filtra por tipo.
     */
    public function available(string $checkIn, string $checkOut, ?int $roomTypeId = null): Collection;

    /**
     * Cambiar el estado de una habitación
     *
     * @param  int  $id  ID de la habitación
     * @param  string  $status  Nuevo estado (available|occupied|maintenance)
     */
    public function changeStatus(int $id, string $status): Room;

    /**
     * Verificar si una habitación está ocupada
     *
     * Se usa para evitar eliminar una habitación que está en uso.
     *
     *
     * @return bool
     *              - true  → la habitación está ocupada
     *              - false → no está ocupada
     */
    public function isOccupied(int $id): bool;

    /**
     * Bloquear la fila de la habitación (SELECT ... FOR UPDATE) dentro de una
     * transacción. Serializa las reservas concurrentes de la misma habitación.
     */
    public function lockForUpdate(int $id): Room;
}
