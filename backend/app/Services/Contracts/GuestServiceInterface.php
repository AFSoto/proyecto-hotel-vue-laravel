<?php

namespace App\Services\Contracts;

use App\DTOs\CreateGuestDTO;
use App\DTOs\UpdateGuestDTO;
use App\Models\Guest;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * GuestServiceInterface
 *
 * Contrato del servicio de huéspedes.
 */
interface GuestServiceInterface
{
    public function listGuests(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function createGuest(CreateGuestDTO $dto): Guest;

    public function findGuest(int $id): Guest;

    public function updateGuest(int $id, UpdateGuestDTO $dto): Guest;

    public function deleteGuest(int $id): bool;
}
