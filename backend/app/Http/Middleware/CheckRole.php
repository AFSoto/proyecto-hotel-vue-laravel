<?php

namespace App\Http\Middleware;

use Closure; // Permite usar funciones anónimas (Closure) que Laravel usa para ejecutar el siguiente middleware
use Illuminate\Http\Request; // Representa la petición HTTP que llega al servidor (GET, POST, etc.)
use Symfony\Component\HttpFoundation\Response; // Define el tipo de respuesta HTTP (200, 401, 403, etc.)

/**
 * Middleware CheckRole
 *
 * Este middleware se encarga de validar que el usuario autenticado
 * tenga uno de los roles permitidos para acceder a una ruta.
 *
 * Se usa así en rutas (roles separados por COMA):
 * ->middleware('role:admin,receptionist')
 */
class CheckRole
{
    /**
     * Manejar la petición entrante.
     *
     * @param  Request  $request  Petición HTTP actual
     * @param  Closure  $next  Siguiente middleware/controlador
     * @param  string  ...$roles  Roles permitidos (ej: admin, receptionist)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Obtener usuario autenticado usando el guard "api" (JWT)
        $user = auth('api')->user();

        // Si no hay usuario autenticado, devolver error 401 (no autenticado)
        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        // Verificar si el rol del usuario está dentro de los roles permitidos
        if (! in_array($user->role->slug, $roles)) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a este recurso.',
            ], 403);
        }

        // Si pasa todas las validaciones, continuar con la petición
        return $next($request);
    }
}
