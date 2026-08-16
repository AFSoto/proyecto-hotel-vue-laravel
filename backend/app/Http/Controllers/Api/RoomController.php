<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateRoomDTO;
use App\DTOs\UpdateRoomDTO;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Requests\UpdateRoomStatusRequest;
use App\Http\Resources\RoomResource;
use App\Services\Contracts\RoomServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * RoomController
 *
 * Controlador delgado: traduce HTTP → DTO → servicio → Resource.
 * No contiene lógica de negocio ni acceso a datos.
 */
class RoomController extends BaseController
{
    public function __construct(
        private RoomServiceInterface $roomService
    ) {}

    /**
     * Listar habitaciones (paginado + filtros)
     */
    public function index(Request $request): JsonResponse
    {
        // Filtros soportados por el repositorio
        $filters = $request->only(['search', 'status', 'floor', 'room_type_id']);

        $perPage = $request->input('per_page', 15);

        $rooms = $this->roomService->listRooms($perPage, $filters);

        return response()->json([
            'data' => RoomResource::collection($rooms->items()),
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
            ],
            'message' => 'Listado de habitaciones.',
        ]);
    }

    /**
     * Crear una habitación
     */
    public function store(StoreRoomRequest $request): JsonResponse
    {
        $dto = CreateRoomDTO::fromRequest($request->validated());
        $room = $this->roomService->createRoom($dto);

        return response()->json([
            'data' => new RoomResource($room),
            'message' => 'Habitación creada exitosamente.',
        ], 201);
    }

    /**
     * Ver una habitación
     */
    public function show(int $id): JsonResponse
    {
        $room = $this->roomService->findRoom($id);

        return $this->success(
            new RoomResource($room),
            'Detalle de la habitación.'
        );
    }

    /**
     * Actualizar una habitación
     */
    public function update(UpdateRoomRequest $request, int $id): JsonResponse
    {
        $dto = UpdateRoomDTO::fromRequest($request->validated());
        $room = $this->roomService->updateRoom($id, $dto);

        return $this->success(
            new RoomResource($room),
            'Habitación actualizada exitosamente.'
        );
    }

    /**
     * Cambiar el estado de una habitación (recepción)
     */
    public function updateStatus(UpdateRoomStatusRequest $request, int $id): JsonResponse
    {
        $room = $this->roomService->changeRoomStatus($id, $request->validated()['status']);

        return $this->success(
            new RoomResource($room),
            'Estado de la habitación actualizado.'
        );
    }

    /**
     * Eliminar una habitación (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->roomService->deleteRoom($id);

            return $this->success(
                null,
                'Habitación eliminada exitosamente.'
            );
        } catch (ConflictHttpException $e) {
            // Regla de negocio: no se puede borrar una habitación ocupada
            return $this->error($e->getMessage(), 409);
        }
    }
}
