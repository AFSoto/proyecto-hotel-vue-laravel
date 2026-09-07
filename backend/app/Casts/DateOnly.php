<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * DateOnly
 *
 * Cast de fecha SIN hora. Al leer devuelve un Carbon a medianoche (como el cast
 * 'date'); al guardar persiste 'Y-m-d' puro.
 *
 * Motivo: en MySQL una columna DATE trunca la hora, pero en sqlite (los tests)
 * Laravel guardaría 'Y-m-d 00:00:00', y ese sufijo rompe las comparaciones de
 * fecha por string (solapamiento medio-abierto, filtros por fecha). Guardar
 * 'Y-m-d' hace que ambos motores se comporten igual.
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value ? Carbon::parse($value)->startOfDay() : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }
}
