<?php

namespace App\Services;

use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\RoomRepositoryInterface;
use App\Services\Contracts\ReportServiceInterface;
use Illuminate\Support\Carbon;

/**
 * ReportService
 *
 * Arma el resumen de indicadores combinando agregados de reservas y
 * habitaciones. No hace CRUD, así que no extiende BaseService.
 */
class ReportService implements ReportServiceInterface
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private RoomRepositoryInterface $roomRepository,
    ) {}

    public function summary(?string $from = null, ?string $to = null): array
    {
        // Rango por defecto: mes actual
        $from = $from ?: Carbon::now()->startOfMonth()->toDateString();
        $to = $to ?: Carbon::now()->endOfMonth()->toDateString();
        $today = Carbon::today()->toDateString();

        $bookings = $this->bookingRepository->aggregatesBetween($from, $to);
        $rooms = $this->roomRepository->countByStatus();
        $pending = $this->bookingRepository->pendingCounts($today);

        return [
            'range' => ['from' => $from, 'to' => $to],
            'bookings' => [
                'total' => $bookings['total'],
                'by_status' => $bookings['by_status'],
            ],
            'revenue' => $bookings['revenue'],
            'nights_sold' => $bookings['nights_sold'],
            'rooms' => $rooms,
            'today' => $pending,
        ];
    }
}
