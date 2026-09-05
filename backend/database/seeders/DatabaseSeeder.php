<?php

namespace Database\Seeders;

// Modelo User (aunque aquí no lo estás usando directamente)
use App\Models\User;
// Trait para desactivar eventos de modelo durante el seeding
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
// Clase base de seeders
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Evita que se ejecuten eventos (created, updated, etc.)
    use WithoutModelEvents;

    /**
     * Método principal que se ejecuta con:
     * php artisan db:seed
     */
    public function run(): void
    {
        // Esto está comentado:
        // Crea 10 usuarios fake usando factories
        // User::factory(10)->create();

        // Ejecuta otros seeders en orden
        $this->call([
            RoleSeeder::class, // primero roles (importante por FK)
            UserSeeder::class, // luego usuarios
            RoomTypeSeeder::class,
            RoomSeeder::class,
            GuestSeeder::class,   // huéspedes antes que reservas
            BookingSeeder::class, // reservas (dependen de user, rooms y guests)
        ]);
    }
}
