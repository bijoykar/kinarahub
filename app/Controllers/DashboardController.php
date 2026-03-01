<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * DashboardController — Renders the main dashboard page.
 *
 * Full dashboard widgets and data loading will be implemented in Phase 8.
 * This stub provides the route target so that login redirects work.
 */
class DashboardController
{
    /**
     * GET /dashboard — Show the dashboard.
     */
    public function index(Request $request): void
    {
        Response::view('layouts/app', [
            'pageTitle'  => 'Dashboard — Kinara Store Hub',
            'breadcrumb' => [['label' => 'Dashboard']],
            'view'       => dirname(__DIR__, 2) . '/views/dashboard/index.php',
        ]);
    }
}
