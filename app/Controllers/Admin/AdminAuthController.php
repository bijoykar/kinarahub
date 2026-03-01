<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use App\Models\AdminModel;

/**
 * AdminAuthController -- Handles platform admin login and logout.
 *
 * Admin sessions are stored under $_SESSION['admin_id'] using a
 * separate session cookie (KINARAHUB_ADMIN_SESSION) to avoid
 * collisions with store staff sessions.
 */
class AdminAuthController
{
    private AdminModel $model;

    public function __construct()
    {
        $this->model = new AdminModel();
    }

    /**
     * GET /admin/login -- Show the admin login page.
     */
    public function showLogin(Request $request): void
    {
        // If already logged in, redirect to admin dashboard
        if (!empty($_SESSION['admin_id'])) {
            Response::redirect('/kinarahub/admin/dashboard');
        }

        Response::view('admin/login', [
            'error'     => null,
            'csrfToken' => CsrfMiddleware::token(),
        ]);
    }

    /**
     * POST /admin/login -- Validate credentials and create admin session.
     */
    public function login(Request $request): void
    {
        $email    = strtolower(trim($request->post('email', '')));
        $password = $request->post('password', '');

        // Basic validation
        if ($email === '' || $password === '') {
            Response::view('admin/login', [
                'error'     => 'Email and password are required.',
                'csrfToken' => CsrfMiddleware::token(),
            ]);
            return;
        }

        // Find admin by email
        $admin = $this->model->findByEmail($email);

        if ($admin === null || !password_verify($password, $admin['password_hash'])) {
            Response::view('admin/login', [
                'error'     => 'Invalid email or password.',
                'csrfToken' => CsrfMiddleware::token(),
            ]);
            return;
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['admin_id']   = (int) $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];

        Response::redirect('/kinarahub/admin/dashboard');
    }

    /**
     * POST /admin/logout -- Destroy admin session and redirect to login.
     */
    public function logout(Request $request): void
    {
        // Clear impersonation if active
        unset($_SESSION['impersonate_store_id'], $_SESSION['impersonate_store_name']);

        // Clear admin session
        unset($_SESSION['admin_id'], $_SESSION['admin_name']);

        // Destroy the session entirely
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        Response::redirect('/kinarahub/admin/login');
    }
}
