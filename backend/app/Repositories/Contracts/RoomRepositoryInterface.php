<?php

namespace App\Repositories\Contracts;

use App\Models\Room;

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
}
