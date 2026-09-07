<?php

namespace App\Repositories;

use App\Models\Room;
use App\Repositories\Contracts\RoomRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
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
     * Habitaciones disponibles en un rango de fechas.
     *
     * Disponible = NO está en mantenimiento y NO tiene ninguna reserva activa
     * que solape el rango (mismo intervalo semiabierto que hasOverlap, y las
     * canceladas no cuentan). Opcionalmente se filtra por tipo de habitación.
     */
    public function available(string $checkIn, string $checkOut, ?int $roomTypeId = null): Collection
    {
        $query = $this->model->with('roomType')
            ->where('status', '!=', 'maintenance')
            ->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
                $q->where('status', '!=', 'cancelled')
                    ->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn);
            });

        if ($roomTypeId !== null) {
            $query->where('room_type_id', $roomTypeId);
        }

        return $query->orderBy('number', 'asc')->get();
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

    /**
     * Conteo de habitaciones (activas) por estado, con total.
     * Los SoftDeletes excluyen automáticamente las eliminadas.
     */
    public function countByStatus(): array
    {
        $counts = $this->model
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'total' => array_sum($counts),
            'available' => (int) ($counts['available'] ?? 0),
            'occupied' => (int) ($counts['occupied'] ?? 0),
            'maintenance' => (int) ($counts['maintenance'] ?? 0),
        ];
    }

    /**
     * Bloquear la fila de la habitación (SELECT ... FOR UPDATE).
     *
     * Debe llamarse DENTRO de una transacción. Serializa las operaciones de
     * reserva de la MISMA habitación: cualquier segunda transacción sobre esta
     * room espera al COMMIT de la primera, cerrando la ventana de doble-reserva.
     * Respeta SoftDeletes (no bloquea habitaciones eliminadas).
     */
    public function lockForUpdate(int $id): Room
    {
        return $this->model->whereKey($id)->lockForUpdate()->firstOrFail();
    }
}
