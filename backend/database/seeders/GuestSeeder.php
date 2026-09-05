<?php

namespace Database\Seeders;

use App\Models\Guest;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * GuestSeeder
 *
 * Inserta unos huéspedes de ejemplo (idempotente por documento).
 */
class GuestSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $guests = [
            ['full_name' => 'María Fernanda López', 'document_number' => 'CC-1001', 'email' => 'maria@example.com', 'phone' => '3001112233'],
            ['full_name' => 'Carlos Andrés Rojas', 'document_number' => 'CC-1002', 'email' => 'carlos@example.com', 'phone' => '3004445566'],
            ['full_name' => 'Laura Gómez Díaz', 'document_number' => 'CC-1003', 'email' => 'laura@example.com', 'phone' => '3007778899'],
        ];

        foreach ($guests as $g) {
            Guest::firstOrCreate(
                ['document_number' => $g['document_number']], // condición única
                $g + ['document_type' => 'cc'],
            );
        }
    }
}
