<?php

// Importa la clase Route para definir rutas en Laravel
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
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

        // Listado de roles (catálogo para el selector de la pantalla de usuarios)
        Route::get('roles', [RoleController::class, 'index']);

        // CRUD room-types  → se implementa en TASK-BE-012
        // El listado (index) se comparte con recepción más abajo; aquí solo la escritura.
        Route::apiResource('room-types', RoomTypeController::class)->except(['index']);

        // Escritura de habitaciones (crear / editar / eliminar) → solo admin
        Route::post('rooms', [RoomController::class, 'store']);
        Route::put('rooms/{id}', [RoomController::class, 'update']);
        Route::delete('rooms/{id}', [RoomController::class, 'destroy']);
        // Historial        → se implementa en TASK-BE-026

        // Borrado de huéspedes → solo admin (recepción no elimina)
        Route::delete('guests/{id}', [GuestController::class, 'destroy']);
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
        // OJO: 'available' debe ir ANTES de 'rooms/{id}', si no {id} capturaría 'available'
        Route::get('rooms/available', [RoomController::class, 'available']);
        Route::get('rooms/{id}', [RoomController::class, 'show']);
        Route::patch('rooms/{id}/status', [RoomController::class, 'updateStatus']);

        // Listado de tipos de habitación (para poblar filtros/selects del front)
        Route::get('room-types', [RoomTypeController::class, 'index']);

        // Gestión de huéspedes (el borrado va en el grupo solo-admin de arriba)
        Route::get('guests', [GuestController::class, 'index']);
        Route::post('guests', [GuestController::class, 'store']);
        Route::get('guests/{id}', [GuestController::class, 'show']);
        Route::put('guests/{id}', [GuestController::class, 'update']);

        // Gestión de reservas
        Route::get('bookings', [BookingController::class, 'index']);
        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('bookings/{id}', [BookingController::class, 'show']);
        Route::put('bookings/{id}', [BookingController::class, 'update']);
        // Cancelar (cambio de estado, no borrado): admin y recepción
        Route::patch('bookings/{id}/cancel', [BookingController::class, 'cancel']);

        // Check-in / check-out (movimientos de recepción)
        Route::patch('bookings/{id}/check-in', [BookingController::class, 'checkIn']);
        Route::patch('bookings/{id}/check-out', [BookingController::class, 'checkOut']);

        // Reportes / indicadores (dashboard)
        Route::get('reports/summary', [ReportController::class, 'summary']);
    });
});
