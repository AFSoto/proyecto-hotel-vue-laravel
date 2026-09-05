<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\User;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Database\Seeders\RoleSeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\RoomTypeSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BookingOverlapTest
 *
 * Prueba directa de la regla crítica de solapamiento (intervalo semiabierto)
 * a nivel de repositorio, cubriendo los casos frontera.
 */
class BookingOverlapTest extends TestCase
{
    use RefreshDatabase;

    private BookingRepositoryInterface $repo;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
        ]);

        $this->repo = app(BookingRepositoryInterface::class);
        $this->room = Room::where('status', 'available')->firstOrFail();

        // Reserva existente [2026-10-10, 2026-10-15)
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'guest_id' => Guest::factory()->create()->id,
            'user_id' => User::query()->value('id'),
            'check_in_date' => '2026-10-10',
            'check_out_date' => '2026-10-15',
            'status' => 'confirmed',
        ]);
    }

    public function test_rango_que_solapa_es_detectado(): void
    {
        $this->assertTrue($this->repo->hasOverlap($this->room->id, '2026-10-12', '2026-10-14'));
    }

    public function test_fronteras_de_checkout_no_solapan(): void
    {
        // Termina justo cuando empieza la existente → libre
        $this->assertFalse($this->repo->hasOverlap($this->room->id, '2026-10-08', '2026-10-10'));

        // Empieza justo cuando termina la existente → libre
        $this->assertFalse($this->repo->hasOverlap($this->room->id, '2026-10-15', '2026-10-18'));
    }

    public function test_reserva_cancelada_no_bloquea(): void
    {
        Booking::query()->update(['status' => 'cancelled']);

        $this->assertFalse($this->repo->hasOverlap($this->room->id, '2026-10-12', '2026-10-14'));
    }

    public function test_ignore_id_excluye_la_propia_reserva(): void
    {
        $bookingId = Booking::query()->value('id');

        $this->assertFalse(
            $this->repo->hasOverlap($this->room->id, '2026-10-12', '2026-10-14', $bookingId)
        );
    }
}
