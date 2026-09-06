<?php

namespace App\Services\Contracts;

use App\DTOs\CreateBookingDTO;
use App\DTOs\UpdateBookingDTO;
use App\Models\Booking;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * BookingServiceInterface
 *
 * Contrato del servicio de reservas.
 */
interface BookingServiceInterface
{
    public function listBookings(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function createBooking(CreateBookingDTO $dto, int $userId): Booking;

    public function findBooking(int $id): Booking;

    public function updateBooking(int $id, UpdateBookingDTO $dto): Booking;

    public function cancelBooking(int $id): Booking;

    public function checkIn(int $id): Booking;

    public function checkOut(int $id): Booking;
}
