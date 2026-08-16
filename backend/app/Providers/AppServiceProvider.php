<?php

namespace App\Providers;

// Clase base de proveedores de servicios en Laravel
use App\Repositories\Contracts\RoleRepositoryInterface;
// Interfaces (contratos)
use App\Repositories\Contracts\RoomRepositoryInterface;
use App\Repositories\Contracts\RoomTypeRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\RoleRepository;
// Implementaciones concretas
use App\Repositories\RoomRepository;
use App\Repositories\RoomTypeRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
// Service Contracts
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\RoomServiceInterface;
use App\Services\Contracts\RoomTypeServiceInterface;
use App\Services\Contracts\UserServiceInterface;
// Service Implementations
use App\Services\RoomService;
use App\Services\RoomTypeService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider
 *
 * Aquí se registran bindings en el contenedor de servicios de Laravel.
 * Es decir, se define qué clase concreta usar cuando se solicita una interfaz.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Método register()
     *
     * Se ejecuta al iniciar la aplicación.
     * Aquí se registran dependencias (bindings).
     */
    public function register(): void
    {
        // Cuando alguien solicite UserRepositoryInterface,
        // Laravel automáticamente inyectará UserRepository
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Cuando alguien solicite RoleRepositoryInterface,
        // Laravel inyectará RoleRepository
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);

        // Cuando se necesite RoomTypeRepositoryInterface, se resolverá automáticamente RoomTypeRepository
        $this->app->bind(RoomTypeRepositoryInterface::class, RoomTypeRepository::class);

        // Repositorio de habitaciones
        $this->app->bind(RoomRepositoryInterface::class, RoomRepository::class);

        // Services
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(RoomTypeServiceInterface::class, RoomTypeService::class);

        // Servicio de habitaciones
        $this->app->bind(RoomServiceInterface::class, RoomService::class);
    }

    /**
     * Método boot()
     *
     * Se ejecuta después de registrar todos los servicios.
     * Se usa para lógica de arranque (eventos, rutas, etc.)
     */
    public function boot(): void
    {
        //
    }
}
