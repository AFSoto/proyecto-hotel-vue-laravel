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
 * CheckInOutTest
 *
 * Verifica las transiciones de check-in y check-out por la API, incluyendo
 * el efecto en el estado de la habitación y las guardas de negocio.
 */
class CheckInOutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
        ]);
    }

    private function headers(string $email = 'admin@hotel.com'): array
    {
        $token = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'password',
        ])->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    private function crearReserva(array $overrides = []): array
    {
        $guest = Guest::factory()->create();
        $room = Room::where('status', 'available')->firstOrFail();

        $payload = array_merge([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
        ], $overrides);

        $id = $this->withHeaders($this->headers())->postJson('/api/bookings', $payload)->json('data.id');

        return ['id' => $id, 'room_id' => $payload['room_id']];
    }

    public function test_checkin_ocupa_la_habitacion_y_checkout_la_libera(): void
    {
        $headers = $this->headers();
        ['id' => $id, 'room_id' => $roomId] = $this->crearReserva();

        // Check-in
        $this->withHeaders($headers)->patchJson("/api/bookings/{$id}/check-in")
            ->assertOk()
            ->assertJsonPath('data.status', 'checked_in');

        $this->assertNotNull(Room::find($roomId)->status);
        $this->assertSame('occupied', Room::find($roomId)->status);

        // Check-out
        $this->withHeaders($headers)->patchJson("/api/bookings/{$id}/check-out")
            ->assertOk()
            ->assertJsonPath('data.status', 'checked_out');

        $this->assertSame('available', Room::find($roomId)->status);
    }

    public function test_no_permite_checkin_antes_de_la_fecha_de_entrada(): void
    {
        ['id' => $id] = $this->crearReserva([
            'check_in_date' => now()->addDays(5)->toDateString(),
            'check_out_date' => now()->addDays(7)->toDateString(),
        ]);

        $this->withHeaders($this->headers())->patchJson("/api/bookings/{$id}/check-in")
            ->assertStatus(409);
    }

    public function test_no_permite_checkout_sin_checkin(): void
    {
        ['id' => $id] = $this->crearReserva();

        $this->withHeaders($this->headers())->patchJson("/api/bookings/{$id}/check-out")
            ->assertStatus(409);
    }

    public function test_no_permite_checkin_de_una_reserva_cancelada(): void
    {
        $headers = $this->headers();
        ['id' => $id] = $this->crearReserva();

        $this->withHeaders($headers)->patchJson("/api/bookings/{$id}/cancel")->assertOk();

        $this->withHeaders($headers)->patchJson("/api/bookings/{$id}/check-in")
            ->assertStatus(409);
    }
}
