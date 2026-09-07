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

    public function occupancy(?string $from = null, ?string $to = null): array
    {
        // Ventana por defecto: 14 días desde hoy
        $from = $from ?: Carbon::today()->toDateString();
        $to = $to ?: Carbon::parse($from)->addDays(13)->toDateString();

        $rooms = $this->roomRepository->getAll()
            ->load('roomType')
            ->sortBy('number')
            ->values()
            ->map(fn ($r) => [
                'id' => $r->id,
                'number' => $r->number,
                'floor' => $r->floor,
                'status' => $r->status,
                'room_type' => $r->roomType ? ['id' => $r->roomType->id, 'name' => $r->roomType->name] : null,
            ]);

        $bookings = $this->bookingRepository->overlappingBetween($from, $to)
            ->map(fn ($b) => [
                'id' => $b->id,
                'room_id' => $b->room_id,
                'guest_name' => $b->guest?->full_name,
                'check_in_date' => $b->check_in_date->toDateString(),
                'check_out_date' => $b->check_out_date->toDateString(),
                'status' => $b->status,
            ])
            ->values();

        return [
            'range' => ['from' => $from, 'to' => $to],
            'rooms' => $rooms,
            'bookings' => $bookings,
        ];
    }
}
