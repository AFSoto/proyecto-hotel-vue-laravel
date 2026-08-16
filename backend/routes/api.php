<?php

// Importa la clase Route para definir rutas en Laravel
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomTypeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ==============================
// 🔓 RUTAS PÚBLICAS (sin token)
// ==============================

// Agrupa rutas bajo el prefijo /api/auth
// Ejemplo: /api/auth/login
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// ==============================
// 🔐 RUTAS PROTEGIDAS (requieren JWT)
// ==============================

// Este middleware verifica que el usuario tenga un token válido (JWT)
// 'auth:api' significa que usas autenticación tipo API (como JWT)
Route::middleware('auth:api')->group(function () {

    // ------------------------------
    // 🔐 RUTAS DE AUTENTICACIÓN
    // ------------------------------

    // Prefijo /api/auth
    Route::prefix('auth')->group(function () {
        // GET /api/auth/me
        // Devuelve los datos del usuario autenticado

        Route::get('me', [AuthController::class, 'me']);

        // POST /api/auth/logout
        // Cierra sesión (invalida el token)
        Route::post('logout', [AuthController::class, 'logout']);

        // POST /api/auth/refresh
        // Genera un nuevo token JWT
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // ------------------------------
    // 👑 SOLO ADMIN
    // ------------------------------

    // Middleware personalizado 'role:admin'
    // Solo usuarios con rol "admin" pueden acceder
    Route::middleware('role:admin')->group(function () {

        // CRUD usuarios
        // Registra automáticamente todas las rutas RESTful para el recurso "users"
        Route::apiResource('users', UserController::class);

        // CRUD room-types  → se implementa en TASK-BE-012
        Route::apiResource('room-types', RoomTypeController::class);

        // Escritura de habitaciones (crear / editar / eliminar) → solo admin
        Route::post('rooms', [RoomController::class, 'store']);
        Route::put('rooms/{id}', [RoomController::class, 'update']);
        Route::delete('rooms/{id}', [RoomController::class, 'destroy']);
        // Historial        → se implementa en TASK-BE-026
    });

    // ------------------------------
    // 👥 ADMIN Y RECEPCIONISTA
    // ------------------------------

    // Aquí irán rutas compartidas entre roles

    // Ejemplos:
    // rooms (habitaciones)
    // bookings (reservas)
    // check-ins
    // check-outs
    //
    // NOTA: el middleware 'role' separa los roles por COMA, no por pipe,
    // y el slug real del recepcionista es 'receptionist' (ver seeder).
    Route::middleware('role:admin,receptionist')->group(function () {

        // Lectura y cambio de estado de habitaciones (admin y recepcionista)
        Route::get('rooms', [RoomController::class, 'index']);
        Route::get('rooms/{id}', [RoomController::class, 'show']);
        Route::patch('rooms/{id}/status', [RoomController::class, 'updateStatus']);
    });
});
