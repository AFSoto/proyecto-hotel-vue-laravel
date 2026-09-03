<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateBookingDTO;
use App\DTOs\UpdateBookingDTO;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Services\Contracts\BookingServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * BookingController
 *
 * Controlador delgado de reservas: traduce HTTP → DTO → servicio → Resource.
 * Las reglas de negocio (solapamiento, tarifa, estados) viven en el Service.
 */
class BookingController extends BaseController
{
    public function __construct(
        private BookingServiceInterface $bookingService
    ) {}

    /**
     * Listar reservas (paginado + filtros)
     */
    public function index(Request $request): JsonResponse
    {
        // Filtros soportados por el repositorio
        $filters = $request->only(['status', 'room_id', 'guest_id', 'search', 'from', 'to']);

        $perPage = $request->input('per_page', 15);

        $bookings = $this->bookingService->listBookings($perPage, $filters);

        return response()->json([
            'data' => BookingResource::collection($bookings->items()),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
            'message' => 'Listado de reservas.',
        ]);
    }

    /**
     * Crear una reserva
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $dto = CreateBookingDTO::fromRequest($request->validated());

            // El empleado autenticado queda registrado como autor de la reserva
            $booking = $this->bookingService->createBooking($dto, $request->user()->id);

            return response()->json([
                'data' => new BookingResource($booking),
                'message' => 'Reserva creada exitosamente.',
            ], 201);
        } catch (ConflictHttpException $e) {
            // Habitación en mantenimiento o no disponible en el rango
            return $this->error($e->getMessage(), 409);
        }
    }

    /**
     * Ver una reserva
     */
    public function show(int $id): JsonResponse
    {
        $booking = $this->bookingService->findBooking($id);

        return $this->success(
            new BookingResource($booking),
            'Detalle de la reserva.'
        );
    }

    /**
     * Actualizar una reserva (solo si está confirmada)
     */
    public function update(UpdateBookingRequest $request, int $id): JsonResponse
    {
        try {
            $dto = UpdateBookingDTO::fromRequest($request->validated());
            $booking = $this->bookingService->updateBooking($id, $dto);

            return $this->success(
                new BookingResource($booking),
                'Reserva actualizada exitosamente.'
            );
        } catch (ConflictHttpException $e) {
            // Estado no editable, mantenimiento o solapamiento
            return $this->error($e->getMessage(), 409);
        }
    }

    /**
     * Cancelar una reserva (solo desde confirmada)
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->cancelBooking($id);

            return $this->success(
                new BookingResource($booking),
                'Reserva cancelada exitosamente.'
            );
        } catch (ConflictHttpException $e) {
            // Solo se cancelan reservas confirmadas
            return $this->error($e->getMessage(), 409);
        }
    }
}
