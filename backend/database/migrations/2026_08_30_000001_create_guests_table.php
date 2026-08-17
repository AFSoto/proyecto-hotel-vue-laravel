<?php

// Clase base para crear/modificar tablas
use Illuminate\Database\Migrations\Migration;

// Permite definir la estructura de la tabla (columnas)
use Illuminate\Database\Schema\Blueprint;

// Facade para interactuar con el esquema de la base de datos
use Illuminate\Support\Facades\Schema;

/**
 * Migración para la tabla guests (huéspedes)
 *
 * Los huéspedes NO son usuarios del sistema (esos son empleados).
 * Aquí se guardan sus datos para vincularlos a las reservas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {

            // ID autoincremental (clave primaria)
            $table->id();

            // Nombre completo del huésped
            $table->string('full_name', 150);

            // Tipo de documento: cc | ce | passport | other
            $table->string('document_type', 20)->default('cc');

            /**
             * Número de documento
             * Identificador de negocio del huésped (único)
             */
            $table->string('document_number', 30)->unique();

            // Datos de contacto (opcionales)
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();

            // Soft delete + timestamps (convención del proyecto)
            $table->softDeletes();
            $table->timestamps();

            // Índice para búsqueda por nombre
            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
