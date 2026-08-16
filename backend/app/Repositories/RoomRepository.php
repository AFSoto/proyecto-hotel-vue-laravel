<?php

namespace App\Repositories;

use App\Models\Room;
use App\Repositories\Contracts\RoomRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * RoomRepository
 *
 * Única capa que habla Eloquent para las habitaciones.
 *
 * Extiende BaseRepository para reutilizar el CRUD genérico
 * e implementa las consultas específicas del dominio.
 */
class RoomRepository extends BaseRepository implements RoomRepositoryInterface
{
    /**
     * Constructor
     *
     * Inyecta el modelo Room y lo pasa al BaseRepository.
     */
    public function __construct(Room $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener habitaciones paginadas con filtros
     *
     * Carga el tipo de habitación (roomType) para evitar N+1.
     *
     * Filtros soportados:
     * - search (por número)
     * - status (available|occupied|maintenance)
     * - floor
     * - room_type_id
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        // Carga anticipada del tipo de habitación (evita N+1)
        $query = $this->model->with('roomType');

        // Búsqueda por número de habitación
        if (isset($filters['search'])) {
            $query->where('number', 'LIKE', "%{$filters['search']}%");
        }

        // Filtro por estado
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por piso
        if (isset($filters['floor'])) {
            $query->where('floor', $filters['floor']);
        }

        // Filtro por tipo de habitación
        if (isset($filters['room_type_id'])) {
            $query->where('room_type_id', $filters['room_type_id']);
        }

        // Ordena por número y pagina
        return $query
            ->orderBy('number', 'asc')
            ->paginate($perPage);
    }

    /**
     * Cambiar el estado de una habitación
     */
    public function changeStatus(int $id, string $status): Room
    {
        // Busca la habitación o lanza 404
        $room = $this->findByIdOrFail($id);

        // Actualiza solo el estado
        $room->update(['status' => $status]);

        // Devuelve el modelo recargado
        return $room->fresh();
    }

    /**
     * Verificar si una habitación está ocupada
     */
    public function isOccupied(int $id): bool
    {
        // Busca la habitación o lanza 404, y compara su estado
        return $this->findByIdOrFail($id)->status === 'occupied';
    }
}
