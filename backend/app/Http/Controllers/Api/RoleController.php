<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\RoleResource;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * RoleController
 *
 * Endpoint de solo lectura para los roles (dato de referencia fijo:
 * admin / receptionist). Se usa para poblar el selector de rol en la
 * pantalla de usuarios. Al ser una simple consulta de catálogo, se apoya
 * directamente en el repositorio sin necesidad de un servicio.
 */
class RoleController extends BaseController
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository
    ) {}

    /**
     * Listar todos los roles.
     */
    public function index(): JsonResponse
    {
        $roles = $this->roleRepository->getAll();

        return $this->success(
            RoleResource::collection($roles),
            'Listado de roles.'
        );
    }
}
