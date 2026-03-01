<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\PermissionMiddleware;
use App\Services\ReportService;

/**
 * ReportController -- Handles all 5 reports: Top Sellers, Aging, P&L, Customer Dues, GST.
 * Also handles CSV and PDF export endpoints.
 */
class ReportController
{
    private ReportService $service;

    public function __construct()
    {
        $this->service = new ReportService();
    }

    // -----------------------------------------------------------------------
    // Report Hub
    // -----------------------------------------------------------------------

    /**
     * GET /reports -- Report index (cards linking to each report).
     */
    public function index(Request $request): void
    {
        Response::view('layouts/app', [
            'pageTitle'  => 'Reports -- Kinara Store Hub',
            'breadcrumb' => [['label' => 'Reports']],
            'view'       => $this->viewPath('reports/index'),
        ]);
    }

    // -----------------------------------------------------------------------
    // 1. Top Sellers
    // -----------------------------------------------------------------------

    /**
     * GET /reports/top-sellers
     */
    public function topSellers(Request $request): void
    {
        $from = (string) ($request->get('from') ?? date('Y-m-01'));
        $to = (string) ($request->get('to') ?? date('Y-m-d'));

        $this->validateDateRange($from, $to);

        $results = $this->service->topSellers($request->storeId, $from, $to);

        $hideCostPrice = $this->isCostPriceHidden($request);

        Response::view('layouts/app', [
            'pageTitle'     => 'Top Sellers -- Kinara Store Hub',
            'breadcrumb'    => [
                ['label' => 'Reports', 'url' => '/kinarahub/reports'],
                ['label' => 'Top Sellers'],
            ],
            'view'          => $this->viewPath('reports/top-sellers'),
            'results'       => $results,
            'filters'       => ['from' => $from, 'to' => $to],
            'hideCostPrice' => $hideCostPrice,
        ]);
    }

    /**
     * GET /reports/top-sellers/export/csv
     */
    public function topSellersCsv(Request $request): void
    {
        $from = (string) ($request->get('from') ?? date('Y-m-01'));
        $to = (string) ($request->get('to') ?? date('Y-m-d'));

        $results = $this->service->topSellers($request->storeId, $from, $to);

        $hideCost = $this->isCostPriceHidden($request);

        $headers = ['Product', 'SKU', 'Qty Sold', 'Revenue'];
        $keys = ['product_name', 'sku', 'qty_sold', 'revenue'];

        if (!$hideCost) {
            $headers = array_merge($headers, ['COGS', 'Gross Profit', 'Margin %']);
            $keys = array_merge($keys, ['cogs', 'gross_profit', 'margin_pct']);
        }

        $this->service->streamCsv(
            "top-sellers-{$from}-to-{$to}.csv",
            $headers,
            $results,
            $keys
        );
    }

    // -----------------------------------------------------------------------
    // 2. Inventory Aging
    // -----------------------------------------------------------------------

    /**
     * GET /reports/aging
     */
    public function aging(Request $request): void
    {
        $days = (int) ($request->get('days') ?? 30);
        if (!in_array($days, [30, 60, 90], true)) {
            $days = 30;
        }

        $results = $this->service->inventoryAging($request->storeId, $days);

        Response::view('layouts/app', [
            'pageTitle'  => 'Inventory Aging -- Kinara Store Hub',
            'breadcrumb' => [
                ['label' => 'Reports', 'url' => '/kinarahub/reports'],
                ['label' => 'Inventory Aging'],
            ],
            'view'    => $this->viewPath('reports/aging'),
            'results' => $results,
            'days'    => $days,
        ]);
    }

    // -----------------------------------------------------------------------
    // 3. Profit & Loss
    // -----------------------------------------------------------------------

    /**
     * GET /reports/pnl
     */
    public function pnl(Request $request): void
    {
        $from = (string) ($request->get('from') ?? date('Y-m-01'));
        $to = (string) ($request->get('to') ?? date('Y-m-d'));

        $this->validateDateRange($from, $to);

        $data = $this->service->profitAndLoss($request->storeId, $from, $to);

        $hideCostPrice = $this->isCostPriceHidden($request);

        Response::view('layouts/app', [
            'pageTitle'     => 'Profit & Loss -- Kinara Store Hub',
            'breadcrumb'    => [
                ['label' => 'Reports', 'url' => '/kinarahub/reports'],
                ['label' => 'Profit & Loss'],
            ],
            'view'          => $this->viewPath('reports/pnl'),
            'summary'       => $data['summary'],
            'breakdown'     => $data['breakdown'],
            'filters'       => ['from' => $from, 'to' => $to],
            'hideCostPrice' => $hideCostPrice,
        ]);
    }

    // -----------------------------------------------------------------------
    // 4. Customer Dues
    // -----------------------------------------------------------------------

    /**
     * GET /reports/customer-dues
     */
    public function customerDues(Request $request): void
    {
        $data = $this->service->customerDues($request->storeId);

        Response::view('layouts/app', [
            'pageTitle'  => 'Customer Dues -- Kinara Store Hub',
            'breadcrumb' => [
                ['label' => 'Reports', 'url' => '/kinarahub/reports'],
                ['label' => 'Customer Dues'],
            ],
            'view'     => $this->viewPath('reports/customer-dues'),
            'results'  => $data['results'],
            'totalDue' => $data['totalDue'],
        ]);
    }

    /**
     * GET /reports/customer-dues/export/csv
     */
    public function customerDuesCsv(Request $request): void
    {
        $data = $this->service->customerDues($request->storeId);

        $this->service->streamCsv(
            'customer-dues-' . date('Y-m-d') . '.csv',
            ['Customer', 'Mobile', 'Credit Total', 'Amount Paid', 'Balance Due'],
            $data['results'],
            ['name', 'mobile', 'credit_total', 'amount_paid', 'balance']
        );
    }

    // -----------------------------------------------------------------------
    // 5. GST Summary
    // -----------------------------------------------------------------------

    /**
     * GET /reports/gst
     */
    public function gst(Request $request): void
    {
        $from = (string) ($request->get('from') ?? date('Y-m-01'));
        $to = (string) ($request->get('to') ?? date('Y-m-d'));

        $this->validateDateRange($from, $to);

        $data = $this->service->gstSummary($request->storeId, $from, $to);

        Response::view('layouts/app', [
            'pageTitle'  => 'GST Summary -- Kinara Store Hub',
            'breadcrumb' => [
                ['label' => 'Reports', 'url' => '/kinarahub/reports'],
                ['label' => 'GST Summary'],
            ],
            'view'    => $this->viewPath('reports/gst'),
            'results' => $data['results'],
            'totals'  => $data['totals'],
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    /**
     * GET /reports/gst/export/csv
     */
    public function gstCsv(Request $request): void
    {
        $from = (string) ($request->get('from') ?? date('Y-m-01'));
        $to = (string) ($request->get('to') ?? date('Y-m-d'));

        $data = $this->service->gstSummary($request->storeId, $from, $to);

        $this->service->streamCsv(
            "gst-summary-{$from}-to-{$to}.csv",
            ['Period', 'Total Sales', 'GST Amount'],
            $data['results'],
            ['period', 'total_sales', 'tax_amount']
        );
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function viewPath(string $name): string
    {
        return dirname(__DIR__, 2) . '/views/' . $name . '.php';
    }

    /**
     * Check if cost_price field is hidden for the current role.
     */
    private function isCostPriceHidden(Request $request): bool
    {
        $pdo = \App\Core\Database::getInstance();
        $hiddenFields = PermissionMiddleware::getRestrictedFields($request->roleId, $pdo);

        return in_array('cost_price', $hiddenFields, true);
    }

    /**
     * Validate and sanitize date range. Defaults to current month on invalid input.
     */
    private function validateDateRange(string &$from, string &$to): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || strtotime($from) === false) {
            $from = date('Y-m-01');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || strtotime($to) === false) {
            $to = date('Y-m-d');
        }
        // Ensure from <= to.
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
    }
}
