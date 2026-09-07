<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

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

    /**
     * Agregados de reservas cuya ENTRADA cae en el rango [from, to].
     *
     * - by_status: conteo por estado (incluye canceladas)
     * - revenue / nights_sold: solo reservas NO canceladas
     * Las noches se suman en PHP para no depender de SQL específico del motor.
     */
    public function aggregatesBetween(string $from, string $to): array
    {
        $base = $this->model->whereBetween('check_in_date', [$from, $to]);

        $counts = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $byStatus = [
            'confirmed' => (int) ($counts['confirmed'] ?? 0),
            'checked_in' => (int) ($counts['checked_in'] ?? 0),
            'checked_out' => (int) ($counts['checked_out'] ?? 0),
            'cancelled' => (int) ($counts['cancelled'] ?? 0),
        ];

        $noCanceladas = (clone $base)->where('status', '!=', 'cancelled');

        $revenue = (float) (clone $noCanceladas)->sum('total_price');

        $nights = 0;
        foreach ((clone $noCanceladas)->get(['check_in_date', 'check_out_date']) as $b) {
            $nights += (int) round(
                Carbon::parse($b->check_in_date)->diffInDays(Carbon::parse($b->check_out_date))
            );
        }

        return [
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
            'revenue' => round($revenue, 2),
            'nights_sold' => $nights,
        ];
    }

    /**
     * Reservas NO canceladas que solapan la ventana [from, to] (para el
     * tablero de ocupación). Trae huésped y habitación. Intervalo medio-abierto:
     * solapa si check_in_date <= to AND check_out_date > from.
     */
    public function overlappingBetween(string $from, string $to): Collection
    {
        return $this->model->with(['guest', 'room'])
            ->where('status', '!=', 'cancelled')
            ->where('check_in_date', '<=', $to)
            ->where('check_out_date', '>', $from)
            ->orderBy('check_in_date')
            ->get();
    }

    /**
     * Conteos pendientes a una fecha: llegadas (confirmadas con entrada <= fecha)
     * y salidas (con check-in hecho y salida <= fecha).
     */
    public function pendingCounts(string $date): array
    {
        return [
            'arrivals' => (int) $this->model
                ->where('status', 'confirmed')
                ->where('check_in_date', '<=', $date)
                ->count(),
            'departures' => (int) $this->model
                ->where('status', 'checked_in')
                ->where('check_out_date', '<=', $date)
                ->count(),
        ];
    }
}
