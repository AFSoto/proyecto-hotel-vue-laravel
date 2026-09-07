<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Room;
use Database\Seeders\RoleSeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\RoomTypeSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * OccupancyReportTest
 *
 * Verifica el tablero de ocupación: lista habitaciones, incluye reservas
 * que solapan la ventana con el nombre del huésped, y excluye canceladas.
 */
class OccupancyReportTest extends TestCase
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

    private function headers(): array
    {
        $token = $this->postJson('/api/auth/login', [
            'email' => 'admin@hotel.com',
            'password' => 'password',
        ])->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    private function crear(array $payload): int
    {
        return $this->withHeaders($this->headers())
            ->postJson('/api/bookings', $payload)->json('data.id');
    }

    public function test_ocupacion_lista_habitaciones_y_reservas_de_la_ventana(): void
    {
        $guest = Guest::factory()->create(['full_name' => 'María López']);
        $room = Room::where('status', 'available')->firstOrFail();

        // Reserva dentro de la ventana
        $this->crear([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => '2026-10-05',
            'check_out_date' => '2026-10-08',
        ]);

        // Cancelada: NO debe aparecer
        $cancelada = $this->crear([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => '2026-10-15',
            'check_out_date' => '2026-10-17',
        ]);
        $this->withHeaders($this->headers())->patchJson("/api/bookings/{$cancelada}/cancel")->assertOk();

        $res = $this->withHeaders($this->headers())
            ->getJson('/api/reports/occupancy?from=2026-10-01&to=2026-10-14')
            ->assertOk();

        // Todas las habitaciones aparecen como filas
        $this->assertCount(10, $res->json('data.rooms'));

        // Solo la reserva no cancelada dentro de la ventana
        $bookings = $res->json('data.bookings');
        $this->assertCount(1, $bookings);
        $this->assertSame('María López', $bookings[0]['guest_name']);
        $this->assertSame($room->id, $bookings[0]['room_id']);
        $this->assertSame('2026-10-05', $bookings[0]['check_in_date']);
    }

    public function test_ventana_por_defecto_son_14_dias(): void
    {
        $res = $this->withHeaders($this->headers())
            ->getJson('/api/reports/occupancy')
            ->assertOk();

        $from = $res->json('data.range.from');
        $to = $res->json('data.range.to');

        $this->assertSame(13, (int) Carbon::parse($from)->diffInDays(Carbon::parse($to)));
    }
}
