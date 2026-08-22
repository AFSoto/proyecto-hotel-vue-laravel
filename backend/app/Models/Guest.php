<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Guest (huésped)
 *
 * Los huéspedes NO son usuarios del sistema (esos son empleados).
 * Un huésped puede tener muchas reservas.
 */
class Guest extends Model
{
    use SoftDeletes;

    /**
     * Campos asignables masivamente
     */
    protected $fillable = [
        'full_name',
        'document_type',
        'document_number',
        'email',
        'phone',
    ];

    // ─── Relaciones ─────────────────────────────────

    /**
     * Relación: un huésped tiene muchas reservas
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
