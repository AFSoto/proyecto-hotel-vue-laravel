<?php

namespace App\Repositories;

use App\Models\Guest;
use App\Repositories\Contracts\GuestRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * GuestRepository
 *
 * Única capa Eloquent para huéspedes. Hereda el CRUD de BaseRepository
 * y añade las consultas propias del dominio.
 */
class GuestRepository extends BaseRepository implements GuestRepositoryInterface
{
    public function __construct(Guest $model)
    {
        parent::__construct($model);
    }

    /**
     * Listado paginado con búsqueda por nombre o documento.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('document_number', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('full_name', 'asc')->paginate($perPage);
    }

    /**
     * ¿El huésped tiene reservas activas (confirmed | checked_in)?
     */
    public function hasActiveBookings(int $id): bool
    {
        return $this->findByIdOrFail($id)
            ->bookings()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->exists();
    }

    /**
     * Buscar un huésped ELIMINADO (soft delete) por su documento.
     */
    public function findTrashedByDocument(string $documentNumber): ?Guest
    {
        return $this->model->onlyTrashed()
            ->where('document_number', $documentNumber)
            ->first();
    }
}
