<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\DashboardService;

/**
 * DashboardController — Renders the dashboard and serves chart data via AJAX.
 */
class DashboardController
{
    private DashboardService $service;

    public function __construct()
    {
        $this->service = new DashboardService();
    }

    /**
     * GET /dashboard — Show the dashboard with all KPI data.
     */
    public function index(Request $request): void
    {
        $stats = $this->service->getAllStats($request->storeId);

        Response::view('layouts/app', [
            'pageTitle'  => 'Dashboard — Kinara Store Hub',
            'breadcrumb' => [['label' => 'Dashboard']],
            'view'       => dirname(__DIR__, 2) . '/views/dashboard/index.php',
            'stats'      => $stats,
        ]);
    }

    /**
     * GET /dashboard/chart-data — Return chart data as JSON for AJAX requests.
     *
     * Query params: ?type=sales_trend|payment_breakdown&period=day|week|month|year
     */
    public function chartData(Request $request): void
    {
        $type   = (string) ($request->get('type') ?? 'sales_trend');
        $period = (string) ($request->get('period') ?? 'week');

        // Validate period.
        if (!in_array($period, ['day', 'week', 'month', 'year'], true)) {
            $period = 'week';
        }

        $data = match ($type) {
            'sales_trend'       => $this->service->salesTrend($request->storeId, $period),
            'payment_breakdown' => $this->service->paymentMethodBreakdown($request->storeId, $period),
            'stock_distribution' => $this->service->stockStatusDistribution($request->storeId),
            default              => $this->service->salesTrend($request->storeId, $period),
        };

        Response::json($data);
    }
}
