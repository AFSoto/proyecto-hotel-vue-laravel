<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Booking (reserva)
 *
 * Vincula un huésped, una habitación y el empleado que la registra,
 * para un rango de fechas semiabierto [check_in_date, check_out_date).
 */
class Booking extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Campos asignables masivamente
     */
    protected $fillable = [
        'guest_id',
        'room_id',
        'user_id',
        'check_in_date',
        'check_out_date',
        'status',
        'total_price',
        'notes',
    ];

    /**
     * Casts
     *
     * Fechas como date (sin hora) y precio con 2 decimales.
     */
    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'total_price' => 'decimal:2',
        ];
    }

    // ─── Relaciones ─────────────────────────────────

    /**
     * Reserva pertenece a un huésped
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Reserva pertenece a una habitación
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Reserva pertenece al empleado que la registró
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ─────────────────────────────────────

    /**
     * Scope: reservas que NO están canceladas
     * (las canceladas no bloquean disponibilidad)
     */
    public function scopeNotCancelled($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }
}
