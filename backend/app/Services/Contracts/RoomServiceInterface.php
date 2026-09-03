<?php

namespace App\Services\Contracts;

use App\DTOs\CreateRoomDTO;
use App\DTOs\UpdateRoomDTO;
use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * RoomServiceInterface
 *
 * Contrato del servicio para la gestión de habitaciones.
 * Define la lógica de negocio disponible sobre el modelo Room.
 */
interface RoomServiceInterface
{
    /**
     * Listar habitaciones con paginación y filtros
     */
    public function listRooms(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Listar habitaciones disponibles en un rango de fechas (para reservar).
     */
    public function availableRooms(string $checkIn, string $checkOut, ?int $roomTypeId = null): Collection;

    /**
     * Crear una nueva habitación
     */
    public function createRoom(CreateRoomDTO $dto): Room;

    /**
     * Obtener una habitación por ID
     */
    public function findRoom(int $id): Room;

    /**
     * Actualizar una habitación
     */
    public function updateRoom(int $id, UpdateRoomDTO $dto): Room;

    /**
     * Cambiar el estado de una habitación
     */
    public function changeRoomStatus(int $id, string $status): Room;

    /**
     * Eliminar una habitación
     *
     * ⚠️ Regla de negocio: no se puede eliminar una habitación ocupada.
     */
    public function deleteRoom(int $id): bool;
}
