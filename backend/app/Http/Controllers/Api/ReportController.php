<?php

namespace App\Http\Controllers\Api;

use App\Services\Contracts\ReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ReportController
 *
 * Expone los indicadores del negocio para el dashboard.
 */
class ReportController extends BaseController
{
    public function __construct(
        private ReportServiceInterface $reportService
    ) {}

    /**
     * Resumen de indicadores (por rango de fechas de entrada).
     */
    public function summary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $summary = $this->reportService->summary($data['from'] ?? null, $data['to'] ?? null);

        return $this->success($summary, 'Resumen de reportes.');
    }

    /**
     * Tablero de ocupación (habitaciones × días).
     */
    public function occupancy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $occupancy = $this->reportService->occupancy($data['from'] ?? null, $data['to'] ?? null);

        return $this->success($occupancy, 'Ocupación.');
    }
}
