<?php

namespace App\Services\Contracts;

/**
 * ReportServiceInterface
 *
 * Contrato del servicio de reportes/indicadores.
 */
interface ReportServiceInterface
{
    /**
     * Resumen de indicadores para un rango de fechas (por entrada).
     * Si no se pasan fechas, usa el mes actual.
     */
    public function summary(?string $from = null, ?string $to = null): array;

    /**
     * Tablero de ocupación (habitaciones + reservas) para una ventana de fechas.
     * Si no se pasan fechas, usa una ventana de 14 días desde hoy.
     */
    public function occupancy(?string $from = null, ?string $to = null): array;
}
