<?php

// Clase base para crear/modificar tablas
use Illuminate\Database\Migrations\Migration;

// Permite definir la estructura de la tabla (columnas)
use Illuminate\Database\Schema\Blueprint;

// Facade para interactuar con el esquema de la base de datos
use Illuminate\Support\Facades\Schema;

/**
 * Migración para la tabla bookings (reservas)
 *
 * Una reserva vincula un huésped (guest), una habitación (room) y el
 * empleado que la registra (user), para un rango de fechas.
 * La disponibilidad por fechas se resuelve con ESTA tabla, no con rooms.status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {

            // ID autoincremental (clave primaria)
            $table->id();

            /**
             * Huésped de la reserva
             * onDelete('restrict'): no se puede borrar un huésped con reservas
             */
            $table->foreignId('guest_id')
                ->constrained('guests')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            /**
             * Habitación reservada
             */
            $table->foreignId('room_id')
                ->constrained('rooms')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            /**
             * Empleado (admin/recepcionista) que registra la reserva.
             * Se toma del usuario autenticado, nunca del request.
             */
            $table->foreignId('user_id')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            /**
             * Rango de la estancia.
             * Intervalo semiabierto [check_in, check_out): el día de salida
             * queda libre para un nuevo check-in (rotación el mismo día).
             */
            $table->date('check_in_date');
            $table->date('check_out_date');

            /**
             * Estado del ciclo de vida de la reserva
             * (alineado con las variantes de AppBadge en el frontend)
             */
            $table->enum('status', ['confirmed', 'checked_in', 'checked_out', 'cancelled'])
                ->default('confirmed');

            /**
             * Tarifa congelada al crear = base_price del tipo * nº de noches.
             * Se guarda para no depender de cambios futuros del precio.
             */
            $table->decimal('total_price', 10, 2)->nullable();

            // Notas adicionales (opcional)
            $table->text('notes')->nullable();

            // Soft delete + timestamps (convención del proyecto)
            $table->softDeletes();
            $table->timestamps();

            /**
             * Índices
             * El compuesto (room_id, check_in_date, check_out_date) acelera
             * la consulta de solapamiento, que es la operación caliente.
             */
            $table->index('guest_id');
            $table->index('user_id');
            $table->index('status');
            $table->index(['room_id', 'check_in_date', 'check_out_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
