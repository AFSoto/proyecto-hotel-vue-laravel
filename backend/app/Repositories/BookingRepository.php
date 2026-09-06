<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * BookingRepository
 *
 * Única capa Eloquent para reservas. Hereda el CRUD de BaseRepository
 * y añade la consulta de solapamiento y utilidades de dominio.
 */
class BookingRepository extends BaseRepository implements BookingRepositoryInterface
{
    public function __construct(Booking $model)
    {
        parent::__construct($model);
    }

    /**
     * Listado paginado con eager load (evita N+1) y filtros.
     *
     * Filtros: status, room_id, guest_id, search (huésped), from/to (rango).
     * El rango filtra reservas que SOLAPAN [from, to) (misma semántica half-open).
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['guest', 'room.roomType']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['guest_id'])) {
            $query->where('guest_id', $filters['guest_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('guest', function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('document_number', 'LIKE', "%{$search}%");
            });
        }

        if (isset($filters['from'], $filters['to'])) {
            $query->where('check_in_date', '<', $filters['to'])
                ->where('check_out_date', '>', $filters['from']);
        }

        // Entradas hasta una fecha (llegadas pendientes): check_in_date <= X
        if (isset($filters['check_in_to'])) {
            $query->where('check_in_date', '<=', $filters['check_in_to']);
        }

        // Salidas hasta una fecha (salidas pendientes): check_out_date <= X
        if (isset($filters['check_out_to'])) {
            $query->where('check_out_date', '<=', $filters['check_out_to']);
        }

        return $query->orderBy('check_in_date', 'desc')->paginate($perPage);
    }

    /**
     * Consulta de solapamiento (regla crítica del negocio).
     *
     * Intervalo semiabierto: solapan si (existente.check_in < nueva.check_out)
     * AND (existente.check_out > nueva.check_in). El día de check-out queda
     * libre para un nuevo check-in (rotación el mismo día). Excluye canceladas.
     */
    public function hasOverlap(int $roomId, string $checkIn, string $checkOut, ?int $ignoreId = null): bool
    {
        return $this->model
            ->where('room_id', $roomId)
            ->where('status', '!=', 'cancelled')
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Cambiar el estado de una reserva y devolverla recargada con relaciones.
     */
    public function changeStatus(int $id, string $status): Booking
    {
        $booking = $this->findByIdOrFail($id);
        $booking->update(['status' => $status]);

        return $booking->fresh(['guest', 'room.roomType']);
    }

    /**
     * ¿La habitación tiene reservas activas (confirmed | checked_in)?
     */
    public function roomHasActiveBookings(int $roomId): bool
    {
        return $this->model
            ->where('room_id', $roomId)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->exists();
    }
}
