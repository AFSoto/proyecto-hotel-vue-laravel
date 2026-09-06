<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Room;
use Database\Seeders\RoleSeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\RoomTypeSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BookingApiTest
 *
 * Pruebas end-to-end del módulo de reservas a través de la API HTTP
 * (login JWT + endpoints reales), sobre sqlite en memoria.
 */
class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Datos base deterministas: roles, empleados, tipos y habitaciones
        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
        ]);
    }

    private function headersFor(string $email = 'admin@hotel.com'): array
    {
        $token = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'password',
        ])->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    private function availableRoom(): Room
    {
        return Room::where('status', 'available')->firstOrFail();
    }

    public function test_crea_reserva_con_tarifa_congelada(): void
    {
        $guest = Guest::factory()->create();
        $room = $this->availableRoom();
        $basePrice = (float) $room->roomType->base_price;

        $res = $this->withHeaders($this->headersFor())->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => now()->addDays(5)->toDateString(),
            'check_out_date' => now()->addDays(8)->toDateString(),
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.status', 'confirmed');

        // 3 noches * precio base
        $this->assertEquals(round($basePrice * 3, 2), (float) $res->json('data.total_price'));
    }

    public function test_reserva_solapada_devuelve_409(): void
    {
        $guest = Guest::factory()->create();
        $room = $this->availableRoom();
        $headers = $this->headersFor();

        $this->withHeaders($headers)->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => now()->addDays(10)->toDateString(),
            'check_out_date' => now()->addDays(13)->toDateString(),
        ])->assertCreated();

        $this->withHeaders($headers)->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => now()->addDays(12)->toDateString(),
            'check_out_date' => now()->addDays(14)->toDateString(),
        ])->assertStatus(409);
    }

    public function test_checkout_mismo_dia_no_solapa(): void
    {
        // Rotación el mismo día: checkout de una = checkin de la otra → permitido
        $guest = Guest::factory()->create();
        $room = $this->availableRoom();
        $headers = $this->headersFor();

        $this->withHeaders($headers)->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => now()->addDays(10)->toDateString(),
            'check_out_date' => now()->addDays(12)->toDateString(),
        ])->assertCreated();

        $this->withHeaders($headers)->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => now()->addDays(12)->toDateString(),
            'check_out_date' => now()->addDays(14)->toDateString(),
        ])->assertCreated();
    }

    public function test_cancelar_y_luego_recancelar_da_409(): void
    {
        $guest = Guest::factory()->create();
        $room = $this->availableRoom();
        $headers = $this->headersFor();

        $id = $this->withHeaders($headers)->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => now()->addDays(3)->toDateString(),
            'check_out_date' => now()->addDays(5)->toDateString(),
        ])->json('data.id');

        $this->withHeaders($headers)->patchJson("/api/bookings/{$id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->withHeaders($headers)->patchJson("/api/bookings/{$id}/cancel")
            ->assertStatus(409);
    }

    public function test_no_se_puede_borrar_huesped_con_reserva_activa(): void
    {
        $guest = Guest::factory()->create();
        $room = $this->availableRoom();
        $headers = $this->headersFor();

        $bookingId = $this->withHeaders($headers)->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => now()->addDays(3)->toDateString(),
            'check_out_date' => now()->addDays(5)->toDateString(),
        ])->json('data.id');

        // Con reserva activa → 409
        $this->withHeaders($headers)->deleteJson("/api/guests/{$guest->id}")
            ->assertStatus(409);

        // Tras cancelar → 200
        $this->withHeaders($headers)->patchJson("/api/bookings/{$bookingId}/cancel")->assertOk();
        $this->withHeaders($headers)->deleteJson("/api/guests/{$guest->id}")->assertOk();
    }

    public function test_rooms_available_excluye_reservadas_y_mantenimiento(): void
    {
        $guest = Guest::factory()->create();
        $room = $this->availableRoom();
        $headers = $this->headersFor();

        $checkIn = now()->addDays(20)->toDateString();
        $checkOut = now()->addDays(22)->toDateString();

        $this->withHeaders($headers)->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
        ])->assertCreated();

        $res = $this->withHeaders($headers)
            ->getJson("/api/rooms/available?check_in_date={$checkIn}&check_out_date={$checkOut}")
            ->assertOk();

        $ids = collect($res->json('data'))->pluck('id');

        // La habitación reservada no aparece
        $this->assertFalse($ids->contains($room->id));

        // Ninguna en mantenimiento aparece
        foreach (Room::where('status', 'maintenance')->pluck('id') as $mid) {
            $this->assertFalse($ids->contains($mid));
        }
    }

    public function test_recepcionista_no_puede_borrar_huesped(): void
    {
        $guest = Guest::factory()->create();

        $this->withHeaders($this->headersFor('recep@hotel.com'))
            ->deleteJson("/api/guests/{$guest->id}")
            ->assertStatus(403);
    }

    public function test_filtro_check_in_to_devuelve_solo_las_llegadas_hasta_la_fecha(): void
    {
        $guest = Guest::factory()->create();
        $room = $this->availableRoom();
        $headers = $this->headersFor();

        // Llega hoy
        $hoy = $this->withHeaders($headers)->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
        ])->json('data.id');

        // Llega en 10 días (misma habitación, sin solape)
        $futura = $this->withHeaders($headers)->postJson('/api/bookings', [
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => now()->addDays(10)->toDateString(),
            'check_out_date' => now()->addDays(12)->toDateString(),
        ])->json('data.id');

        $res = $this->withHeaders($headers)->getJson(
            '/api/bookings?status=confirmed&check_in_to='.now()->toDateString()
        )->assertOk();

        $ids = collect($res->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($hoy));
        $this->assertFalse($ids->contains($futura));
    }
}
