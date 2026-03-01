<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\AdminModel;

/**
 * AdminDashboardController -- Platform admin dashboard.
 *
 * Shows platform-wide statistics: store counts by status, total sales
 * volume, and total revenue across all stores.
 */
class AdminDashboardController
{
    private AdminModel $model;

    public function __construct()
    {
        $this->model = new AdminModel();
    }

    /**
     * GET /admin/dashboard -- Render the admin dashboard.
     */
    public function index(Request $request): void
    {
        $storeStats = $this->model->storeStats();
        $salesStats = $this->model->salesStats();

        $stats = array_merge($storeStats, $salesStats);

        Response::view('layouts/admin', [
            'pageTitle'  => 'Dashboard -- Admin',
            'breadcrumb' => [
                ['label' => 'Dashboard'],
            ],
            'view'       => $this->viewPath('admin/dashboard'),
            'stats'      => $stats,
        ]);
    }

    /**
     * Resolve a view template path.
     */
    private function viewPath(string $template): string
    {
        return dirname(__DIR__, 3) . '/views/' . $template . '.php';
    }
}
