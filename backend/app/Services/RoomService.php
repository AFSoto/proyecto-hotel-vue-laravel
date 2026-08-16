<?php

namespace App\Services;

use App\DTOs\CreateRoomDTO;
use App\DTOs\UpdateRoomDTO;
use App\Models\Room;
use App\Repositories\Contracts\RoomRepositoryInterface;
use App\Services\Contracts\RoomServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * RoomService
 *
 * Lógica de negocio de las habitaciones. Intermedia entre el
 * Controller y el Repository; aquí viven las reglas del dominio.
 */
class RoomService extends BaseService implements RoomServiceInterface
{
    /**
     * Constructor
     *
     * Inyecta el repositorio por su interfaz (desacoplamiento).
     */
    public function __construct(
        private RoomRepositoryInterface $roomRepository
    ) {
        parent::__construct($roomRepository);
    }

    /**
     * Listar habitaciones con paginación y filtros
     */
    public function listRooms(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->roomRepository->paginate($perPage, $filters);
    }

    /**
     * Crear una nueva habitación
     *
     * Devuelve la habitación con su tipo cargado (para el Resource).
     */
    public function createRoom(CreateRoomDTO $dto): Room
    {
        return $this->roomRepository->create($dto->toArray())->load('roomType');
    }

    /**
     * Obtener una habitación por ID (con su tipo)
     */
    public function findRoom(int $id): Room
    {
        return $this->roomRepository->findByIdOrFail($id)->load('roomType');
    }

    /**
     * Actualizar una habitación
     */
    public function updateRoom(int $id, UpdateRoomDTO $dto): Room
    {
        return $this->roomRepository->update($id, $dto->toArray())->load('roomType');
    }

    /**
     * Cambiar el estado de una habitación
     */
    public function changeRoomStatus(int $id, string $status): Room
    {
        return $this->roomRepository->changeStatus($id, $status)->load('roomType');
    }

    /**
     * Eliminar una habitación
     *
     * ⚠️ Regla de negocio crítica:
     * - No permitir eliminar una habitación ocupada.
     */
    public function deleteRoom(int $id): bool
    {
        // Si está ocupada, es un conflicto de negocio (409)
        if ($this->roomRepository->isOccupied($id)) {
            throw new ConflictHttpException(
                'No se puede eliminar una habitación que está ocupada.'
            );
        }

        // Soft delete
        return $this->roomRepository->delete($id);
    }
}
