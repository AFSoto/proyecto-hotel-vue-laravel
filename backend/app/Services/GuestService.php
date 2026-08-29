<?php

namespace App\Services;

use App\DTOs\CreateGuestDTO;
use App\DTOs\UpdateGuestDTO;
use App\Models\Guest;
use App\Repositories\Contracts\GuestRepositoryInterface;
use App\Services\Contracts\GuestServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * GuestService
 *
 * Lógica de negocio de los huéspedes.
 */
class GuestService extends BaseService implements GuestServiceInterface
{
    public function __construct(
        private GuestRepositoryInterface $guestRepository
    ) {
        parent::__construct($guestRepository);
    }

    public function listGuests(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->guestRepository->paginate($perPage, $filters);
    }

    /**
     * Crear un huésped.
     *
     * Si ya existe uno ELIMINADO (soft delete) con el mismo documento, se
     * restaura y actualiza en vez de duplicar: el índice único del documento
     * incluye a los borrados, así que crear otra fila fallaría.
     */
    public function createGuest(CreateGuestDTO $dto): Guest
    {
        $trashed = $this->guestRepository->findTrashedByDocument($dto->documentNumber);

        if ($trashed) {
            $trashed->restore();
            $trashed->update($dto->toArray());

            return $trashed->fresh();
        }

        return $this->guestRepository->create($dto->toArray());
    }

    public function findGuest(int $id): Guest
    {
        return $this->guestRepository->findByIdOrFail($id);
    }

    public function updateGuest(int $id, UpdateGuestDTO $dto): Guest
    {
        return $this->guestRepository->update($id, $dto->toArray());
    }

    /**
     * Eliminar (soft delete) un huésped.
     *
     * ⚠️ Regla de negocio: no se permite si tiene reservas activas.
     */
    public function deleteGuest(int $id): bool
    {
        if ($this->guestRepository->hasActiveBookings($id)) {
            throw new ConflictHttpException(
                'No se puede eliminar un huésped con reservas activas.'
            );
        }

        return $this->guestRepository->delete($id);
    }
}
