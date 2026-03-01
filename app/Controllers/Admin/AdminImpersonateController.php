<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\AdminModel;

/**
 * AdminImpersonateController -- Admin "Browse Store (View Only)" feature.
 *
 * When impersonation is active, $_SESSION['impersonate_store_id'] is set.
 * The store-side app.php layout detects this and shows an amber banner
 * with "Viewing as [Store Name] -- Read Only | Exit".
 *
 * All write operations (POST/PUT/DELETE) on store-side routes must be
 * blocked during impersonation. This is enforced in the store-side
 * AuthMiddleware or inline in admin/index.php.
 */
class AdminImpersonateController
{
    private AdminModel $model;

    public function __construct()
    {
        $this->model = new AdminModel();
    }

    /**
     * POST /admin/impersonate/:id -- Enter impersonation mode for a store.
     *
     * Sets impersonation session variables and redirects to the store's
     * dashboard. The admin can browse read-only; all POST actions are blocked.
     */
    public function impersonate(Request $request): void
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

        // Set impersonation session flags.
        // These are read by the store-side layout (app.php) to show the
        // impersonation banner and by AuthMiddleware to block write ops.
        $_SESSION['impersonate_store_id']   = (int) $store['id'];
        $_SESSION['impersonate_store_name'] = $store['name'];

        // Also set the store session variables so store-side pages render correctly.
        $_SESSION['store_id']   = (int) $store['id'];
        $_SESSION['store_name'] = $store['name'];
        $_SESSION['store_logo'] = $store['logo_path'] ?? '';

        // Set a minimal staff identity for the impersonation session.
        $_SESSION['staff_name'] = $_SESSION['admin_name'] ?? 'Admin';
        $_SESSION['role_id']    = 1; // Owner-level view access

        Response::redirect('/kinarahub/dashboard');
    }

    /**
     * POST /admin/exit-impersonate -- Exit impersonation mode.
     *
     * Clears all impersonation and store session variables, then
     * redirects back to the admin stores list.
     */
    public function exitImpersonate(Request $request): void
    {
        // Clear impersonation flags
        unset(
            $_SESSION['impersonate_store_id'],
            $_SESSION['impersonate_store_name']
        );

        // Clear store session variables set during impersonation
        unset(
            $_SESSION['store_id'],
            $_SESSION['store_name'],
            $_SESSION['store_logo'],
            $_SESSION['staff_name'],
            $_SESSION['staff_id'],
            $_SESSION['user_id'],
            $_SESSION['role_id']
        );

        Response::redirect('/kinarahub/admin/stores');
    }
}
