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
 * ReportSummaryTest
 *
 * Verifica los indicadores del endpoint de resumen: conteos por estado,
 * ingresos y noches (sin canceladas) y habitaciones por estado.
 */
class ReportSummaryTest extends TestCase
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

    private function crear(array $payload): int
    {
        return $this->withHeaders($this->headers())
            ->postJson('/api/bookings', $payload)->json('data.id');
    }

    public function test_resumen_agrega_estados_ingresos_y_noches(): void
    {
        $guest = Guest::factory()->create();
        $room = Room::where('status', 'available')->firstOrFail();
        $base = (float) $room->roomType->base_price;

        // A: 3 noches (confirmada)
        $this->crear([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => '2026-10-05',
            'check_out_date' => '2026-10-08',
        ]);

        // B: 2 noches (confirmada, misma habitación, sin solape)
        $this->crear([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => '2026-10-10',
            'check_out_date' => '2026-10-12',
        ]);

        // C: cancelada (no cuenta en ingresos/noches, sí en by_status)
        $c = $this->crear([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => '2026-10-20',
            'check_out_date' => '2026-10-22',
        ]);
        $this->withHeaders($this->headers())->patchJson("/api/bookings/{$c}/cancel")->assertOk();

        $res = $this->withHeaders($this->headers())
            ->getJson('/api/reports/summary?from=2026-10-01&to=2026-10-31')
            ->assertOk();

        // Conteos por estado
        $res->assertJsonPath('data.bookings.total', 3)
            ->assertJsonPath('data.bookings.by_status.confirmed', 2)
            ->assertJsonPath('data.bookings.by_status.cancelled', 1);

        // Ingresos = 5 noches * tarifa base (A=3 + B=2), sin la cancelada
        $this->assertEquals(round($base * 5, 2), (float) $res->json('data.revenue'));
        $this->assertSame(5, $res->json('data.nights_sold'));

        // Habitaciones por estado (seeder: 10 rooms, 1 en mantenimiento)
        $res->assertJsonPath('data.rooms.total', 10)
            ->assertJsonPath('data.rooms.maintenance', 1);
    }

    public function test_rango_por_defecto_es_el_mes_actual(): void
    {
        $res = $this->withHeaders($this->headers())
            ->getJson('/api/reports/summary')
            ->assertOk();

        $this->assertNotNull($res->json('data.range.from'));
        $this->assertNotNull($res->json('data.range.to'));
    }
}
