<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * BookingSeeder
 *
 * Crea reservas de ejemplo NO solapadas (cada una en una habitación distinta),
 * usando el empleado admin como autor. Depende de que ya existan usuarios,
 * tipos, habitaciones y huéspedes.
 */
class BookingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // No duplicar si ya hay reservas
        if (Booking::exists()) {
            return;
        }

        $user = User::orderBy('id')->first();
        $guests = Guest::orderBy('id')->take(3)->get();
        $rooms = Room::where('status', 'available')->orderBy('id')->take(3)->get();

        // Si faltan prerequisitos, no sembramos reservas
        if (! $user || $guests->count() < 3 || $rooms->count() < 3) {
            return;
        }

        $base = Carbon::today()->addDays(7);

        // Rooms distintas + fechas futuras → nunca se solapan entre sí
        $plan = [
            ['room' => $rooms[0], 'guest' => $guests[0], 'in' => $base->copy(), 'nights' => 3],
            ['room' => $rooms[1], 'guest' => $guests[1], 'in' => $base->copy()->addDays(2), 'nights' => 2],
            ['room' => $rooms[2], 'guest' => $guests[2], 'in' => $base->copy()->addDays(10), 'nights' => 4],
        ];

        foreach ($plan as $p) {
            $checkIn = $p['in'];
            $checkOut = $checkIn->copy()->addDays($p['nights']);
            $basePrice = (float) $p['room']->roomType->base_price;

            Booking::create([
                'guest_id' => $p['guest']->id,
                'room_id' => $p['room']->id,
                'user_id' => $user->id,
                'check_in_date' => $checkIn->toDateString(),
                'check_out_date' => $checkOut->toDateString(),
                'status' => 'confirmed',
                'total_price' => round($basePrice * $p['nights'], 2),
                'notes' => null,
            ]);
        }
    }
}
