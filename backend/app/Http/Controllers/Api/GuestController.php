<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateGuestDTO;
use App\DTOs\UpdateGuestDTO;
use App\Http\Requests\StoreGuestRequest;
use App\Http\Requests\UpdateGuestRequest;
use App\Http\Resources\GuestResource;
use App\Services\Contracts\GuestServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * GuestController
 *
 * Controlador delgado de huéspedes: traduce HTTP → DTO → servicio → Resource.
 * No contiene lógica de negocio ni acceso a datos.
 */
class GuestController extends BaseController
{
    public function __construct(
        private GuestServiceInterface $guestService
    ) {}

    /**
     * Listar huéspedes (paginado + búsqueda)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search']);

        $perPage = $request->input('per_page', 15);

        $guests = $this->guestService->listGuests($perPage, $filters);

        return response()->json([
            'data' => GuestResource::collection($guests->items()),
            'meta' => [
                'current_page' => $guests->currentPage(),
                'last_page' => $guests->lastPage(),
                'per_page' => $guests->perPage(),
                'total' => $guests->total(),
            ],
            'message' => 'Listado de huéspedes.',
        ]);
    }

    /**
     * Crear un huésped
     */
    public function store(StoreGuestRequest $request): JsonResponse
    {
        $dto = CreateGuestDTO::fromRequest($request->validated());
        $guest = $this->guestService->createGuest($dto);

        return response()->json([
            'data' => new GuestResource($guest),
            'message' => 'Huésped creado exitosamente.',
        ], 201);
    }

    /**
     * Ver un huésped
     */
    public function show(int $id): JsonResponse
    {
        $guest = $this->guestService->findGuest($id);

        return $this->success(
            new GuestResource($guest),
            'Detalle del huésped.'
        );
    }

    /**
     * Actualizar un huésped
     */
    public function update(UpdateGuestRequest $request, int $id): JsonResponse
    {
        $dto = UpdateGuestDTO::fromRequest($request->validated());
        $guest = $this->guestService->updateGuest($id, $dto);

        return $this->success(
            new GuestResource($guest),
            'Huésped actualizado exitosamente.'
        );
    }

    /**
     * Eliminar un huésped (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->guestService->deleteGuest($id);

            return $this->success(
                null,
                'Huésped eliminado exitosamente.'
            );
        } catch (ConflictHttpException $e) {
            // Regla de negocio: no se puede borrar un huésped con reservas activas
            return $this->error($e->getMessage(), 409);
        }
    }
}
