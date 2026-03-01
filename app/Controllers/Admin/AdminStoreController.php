<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use App\Models\AdminModel;

/**
 * AdminStoreController -- Platform admin store management.
 *
 * Provides paginated listing of all stores, individual store detail view,
 * and status change actions (activate / suspend).
 */
class AdminStoreController
{
    private AdminModel $model;

    public function __construct()
    {
        $this->model = new AdminModel();
    }

    /**
     * GET /admin/stores -- Paginated store listing with search and status filter.
     */
    public function index(Request $request): void
    {
        $page    = max(1, (int) $request->get('page', '1'));
        $perPage = 25;
        $search  = trim($request->get('search', ''));
        $status  = trim($request->get('status', ''));

        $result = $this->model->allStores($page, $perPage, $search, $status);

        $total      = $result['total'];
        $totalPages = max(1, (int) ceil($total / $perPage));

        Response::view('layouts/admin', [
            'pageTitle'   => 'Stores -- Admin',
            'breadcrumb'  => [
                ['label' => 'Stores', 'url' => '/kinarahub/admin/stores'],
            ],
            'view'        => $this->viewPath('admin/stores/index'),
            'stores'      => $result['stores'],
            'pagination'  => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $totalPages,
            ],
            'filters'     => [
                'search' => $search,
                'status' => $status,
            ],
            'csrfToken'   => CsrfMiddleware::token(),
        ]);
    }

    /**
     * GET /admin/stores/:id -- Store detail page.
     */
    public function show(Request $request): void
    {
        $storeId = (int) ($request->params['id'] ?? 0);

        if ($storeId <= 0) {
            Response::redirect('/kinarahub/admin/stores');
        }

        $store = $this->model->storeDetail($storeId);

        if ($store === null) {
            $_SESSION['_flash'] = [['type' => 'error', 'message' => 'Store not found.']];
            Response::redirect('/kinarahub/admin/stores');
        }

        // Map DB columns to what the view expects
        $storeView = [
            'id'          => $store['id'],
            'name'        => $store['name'],
            'owner_name'  => $store['owner_name'],
            'email'       => $store['email'],
            'mobile'      => $store['mobile'] ?? '',
            'address'     => $store['address_street'] ?? '',
            'city'        => $store['address_city'] ?? '',
            'state'       => $store['address_state'] ?? '',
            'pincode'     => $store['address_pincode'] ?? '',
            'logo'        => $store['logo_path'] ?? '',
            'status'      => $store['status'],
            'created_at'  => $store['created_at'],
            'staff_count'   => (int) ($store['staff_count'] ?? 0),
            'product_count' => (int) ($store['product_count'] ?? 0),
            'total_sales'   => (int) ($store['total_sales'] ?? 0),
        ];

        Response::view('layouts/admin', [
            'pageTitle'   => htmlspecialchars($store['name'], ENT_QUOTES, 'UTF-8') . ' -- Admin',
            'breadcrumb'  => [
                ['label' => 'Stores', 'url' => '/kinarahub/admin/stores'],
                ['label' => $store['name']],
            ],
            'view'        => $this->viewPath('admin/stores/detail'),
            'store'       => $storeView,
            'csrfToken'   => CsrfMiddleware::token(),
        ]);
    }

    /**
     * POST /admin/stores/:id/activate -- Set store status to 'active'.
     */
    public function activate(Request $request): void
    {
        $storeId = (int) ($request->params['id'] ?? 0);

        if ($storeId <= 0) {
            Response::redirect('/kinarahub/admin/stores');
        }

        $store = $this->model->storeDetail($storeId);
        if ($store === null) {
            $_SESSION['_flash'] = [['type' => 'error', 'message' => 'Store not found.']];
            Response::redirect('/kinarahub/admin/stores');
        }

        $this->model->updateStoreStatus($storeId, 'active');

        $_SESSION['_flash'] = [['type' => 'success', 'message' => htmlspecialchars($store['name'], ENT_QUOTES, 'UTF-8') . ' has been activated.']];
        Response::redirect('/kinarahub/admin/stores/' . $storeId);
    }

    /**
     * POST /admin/stores/:id/suspend -- Set store status to 'suspended'.
     */
    public function suspend(Request $request): void
    {
        $storeId = (int) ($request->params['id'] ?? 0);

        if ($storeId <= 0) {
            Response::redirect('/kinarahub/admin/stores');
        }

        $store = $this->model->storeDetail($storeId);
        if ($store === null) {
            $_SESSION['_flash'] = [['type' => 'error', 'message' => 'Store not found.']];
            Response::redirect('/kinarahub/admin/stores');
        }

        $this->model->updateStoreStatus($storeId, 'suspended');

        $_SESSION['_flash'] = [['type' => 'warning', 'message' => htmlspecialchars($store['name'], ENT_QUOTES, 'UTF-8') . ' has been suspended.']];
        Response::redirect('/kinarahub/admin/stores/' . $storeId);
    }

    /**
     * Resolve a view template path.
     */
    private function viewPath(string $template): string
    {
        return dirname(__DIR__, 3) . '/views/' . $template . '.php';
    }
}
