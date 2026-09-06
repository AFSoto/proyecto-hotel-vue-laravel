<?php

namespace App\Services;

use App\DTOs\CreateBookingDTO;
use App\DTOs\UpdateBookingDTO;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\RoomRepositoryInterface;
use App\Services\Contracts\BookingServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * BookingService
 *
 * Lógica de negocio de las reservas. Aquí viven la transacción con bloqueo
 * de la habitación, la validación de solapamiento y mantenimiento, el
 * congelado de la tarifa y las guardas de estado (editar/cancelar).
 */
class BookingService extends BaseService implements BookingServiceInterface
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private RoomRepositoryInterface $roomRepository,
    ) {
        parent::__construct($bookingRepository);
    }

    public function listBookings(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->bookingRepository->paginate($perPage, $filters);
    }

    /**
     * Crear una reserva de forma atómica y sin doble-reserva.
     */
    public function createBooking(CreateBookingDTO $dto, int $userId): Booking
    {
        return DB::transaction(function () use ($dto, $userId) {
            // 1) Bloquear la habitación: serializa reservas concurrentes de esa room
            $room = $this->roomRepository->lockForUpdate($dto->roomId);

            // 2) No se reserva una habitación en mantenimiento
            if ($room->status === 'maintenance') {
                throw new ConflictHttpException('La habitación está en mantenimiento y no puede reservarse.');
            }

            // 3) No solapar con otra reserva activa de la misma habitación
            if ($this->bookingRepository->hasOverlap($dto->roomId, $dto->checkInDate, $dto->checkOutDate)) {
                throw new ConflictHttpException('La habitación no está disponible en el rango de fechas seleccionado.');
            }

            // 4) Congelar tarifa = precio base del tipo * nº de noches
            $room->loadMissing('roomType');
            $total = $this->calcularTotal($room->roomType->base_price, $dto->checkInDate, $dto->checkOutDate);

            // 5) Crear con el empleado autenticado y la tarifa congelada
            $data = $dto->toArray() + ['user_id' => $userId, 'total_price' => $total];
            $booking = $this->bookingRepository->create($data);

            return $booking->load(['guest', 'room.roomType']);
        });
    }

    public function findBooking(int $id): Booking
    {
        return $this->bookingRepository->findByIdOrFail($id)->load(['guest', 'room.roomType']);
    }

    /**
     * Actualizar una reserva (solo si está confirmada).
     * Si cambian la habitación o las fechas, revalida solape y recalcula tarifa.
     */
    public function updateBooking(int $id, UpdateBookingDTO $dto): Booking
    {
        return DB::transaction(function () use ($id, $dto) {
            $booking = $this->bookingRepository->findByIdOrFail($id);

            // Solo se editan reservas confirmadas
            if ($booking->status !== 'confirmed') {
                throw new ConflictHttpException('Solo se pueden editar reservas en estado confirmado.');
            }

            $data = $dto->toArray();

            // Valores efectivos: los del DTO si vienen, si no los actuales
            $roomId = $data['room_id'] ?? $booking->room_id;
            $checkIn = $data['check_in_date'] ?? $booking->check_in_date->toDateString();
            $checkOut = $data['check_out_date'] ?? $booking->check_out_date->toDateString();

            $cambiaRoomOFechas = isset($data['room_id'])
                || isset($data['check_in_date'])
                || isset($data['check_out_date']);

            if ($cambiaRoomOFechas) {
                $room = $this->roomRepository->lockForUpdate($roomId);

                if ($room->status === 'maintenance') {
                    throw new ConflictHttpException('La habitación está en mantenimiento y no puede reservarse.');
                }

                // Excluye la propia reserva del solape
                if ($this->bookingRepository->hasOverlap($roomId, $checkIn, $checkOut, $id)) {
                    throw new ConflictHttpException('La habitación no está disponible en el rango de fechas seleccionado.');
                }

                // Recalcular la tarifa congelada
                $room->loadMissing('roomType');
                $data['total_price'] = $this->calcularTotal($room->roomType->base_price, $checkIn, $checkOut);
            }

            $updated = $this->bookingRepository->update($id, $data);

            return $updated->load(['guest', 'room.roomType']);
        });
    }

    /**
     * Cancelar una reserva (solo desde 'confirmed').
     */
    public function cancelBooking(int $id): Booking
    {
        $booking = $this->bookingRepository->findByIdOrFail($id);

        if ($booking->status !== 'confirmed') {
            throw new ConflictHttpException('Solo se pueden cancelar reservas en estado confirmado.');
        }

        return $this->bookingRepository->changeStatus($id, 'cancelled');
    }

    /**
     * Registrar la entrada del huésped (check-in): confirmed → checked_in.
     *
     * Solo desde 'confirmed' y no antes de la fecha de entrada. Marca la
     * habitación como ocupada y guarda la hora real del movimiento.
     */
    public function checkIn(int $id): Booking
    {
        return DB::transaction(function () use ($id) {
            $booking = $this->bookingRepository->findByIdOrFail($id);

            if ($booking->status !== 'confirmed') {
                throw new ConflictHttpException('Solo se puede hacer check-in de una reserva confirmada.');
            }

            // No se puede entrar antes de la fecha de entrada
            if (Carbon::today()->lt($booking->check_in_date)) {
                throw new ConflictHttpException('Aún no es la fecha de entrada de la reserva.');
            }

            $this->bookingRepository->update($id, [
                'status' => 'checked_in',
                'checked_in_at' => Carbon::now(),
            ]);

            // La habitación pasa a ocupada
            $this->roomRepository->changeStatus($booking->room_id, 'occupied');

            return $this->bookingRepository->findByIdOrFail($id)->load(['guest', 'room.roomType']);
        });
    }

    /**
     * Registrar la salida del huésped (check-out): checked_in → checked_out.
     *
     * Solo desde 'checked_in'. Libera la habitación (available) y guarda la
     * hora real del movimiento.
     */
    public function checkOut(int $id): Booking
    {
        return DB::transaction(function () use ($id) {
            $booking = $this->bookingRepository->findByIdOrFail($id);

            if ($booking->status !== 'checked_in') {
                throw new ConflictHttpException('Solo se puede hacer check-out de una reserva con check-in.');
            }

            $this->bookingRepository->update($id, [
                'status' => 'checked_out',
                'checked_out_at' => Carbon::now(),
            ]);

            // La habitación queda libre otra vez
            $this->roomRepository->changeStatus($booking->room_id, 'available');

            return $this->bookingRepository->findByIdOrFail($id)->load(['guest', 'room.roomType']);
        });
    }

    /**
     * Total = tarifa base por noche * nº de noches (mínimo 1).
     * Se calcula sobre fecha-sin-hora para evitar off-by-one.
     */
    private function calcularTotal($basePrice, string $checkIn, string $checkOut): float
    {
        $nights = Carbon::parse($checkIn)->startOfDay()
            ->diffInDays(Carbon::parse($checkOut)->startOfDay());

        $nights = max(1, (int) $nights);

        return round((float) $basePrice * $nights, 2);
    }
}
