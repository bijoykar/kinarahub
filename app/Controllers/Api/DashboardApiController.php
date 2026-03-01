<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\DashboardService;

/**
 * DashboardApiController — REST API for dashboard summary stats.
 */
class DashboardApiController
{
    private DashboardService $service;

    public function __construct()
    {
        $this->service = new DashboardService();
    }

    /**
     * GET /dashboard — Dashboard summary stats for the mobile home screen.
     */
    public function summary(Request $request): void
    {
        $stats = $this->service->getAllStats($request->storeId);

        Response::json([
            'success' => true,
            'data'    => $stats,
            'meta'    => null,
            'error'   => null,
        ]);
    }

    /**
     * GET /dashboard/chart — Chart data for a specific type and period.
     *
     * Query params: type (sales_trend|payment_breakdown|stock_distribution), period (day|week|month|year)
     */
    public function chart(Request $request): void
    {
        $type   = (string) ($request->get('type') ?? 'sales_trend');
        $period = (string) ($request->get('period') ?? 'week');

        if (!in_array($period, ['day', 'week', 'month', 'year'], true)) {
            $period = 'week';
        }

        $data = match ($type) {
            'sales_trend'        => $this->service->salesTrend($request->storeId, $period),
            'payment_breakdown'  => $this->service->paymentMethodBreakdown($request->storeId, $period),
            'stock_distribution' => $this->service->stockStatusDistribution($request->storeId),
            default              => $this->service->salesTrend($request->storeId, $period),
        };

        Response::json([
            'success' => true,
            'data'    => $data,
            'meta'    => null,
            'error'   => null,
        ]);
    }
}
