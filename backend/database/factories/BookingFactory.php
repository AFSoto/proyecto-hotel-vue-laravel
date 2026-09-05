<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Booking>
 *
 * Nota: room_id y user_id se toman de registros ya existentes (sembrados)
 * salvo que se pasen explícitos. guest_id crea un huésped nuevo por defecto.
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        // Rango futuro por defecto (mínimo 1 noche)
        $checkIn = Carbon::today()->addDays(fake()->numberBetween(1, 60));
        $checkOut = $checkIn->copy()->addDays(fake()->numberBetween(1, 5));

        return [
            'guest_id' => Guest::factory(),
            'room_id' => fn () => Room::query()->value('id'),
            'user_id' => fn () => User::query()->value('id'),
            'check_in_date' => $checkIn->toDateString(),
            'check_out_date' => $checkOut->toDateString(),
            'status' => 'confirmed',
            'total_price' => 0,
            'notes' => null,
        ];
    }

    /**
     * Estado cancelado.
     */
    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
