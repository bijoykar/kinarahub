<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use App\Services\StoreService;

/**
 * StoreController — Handles store registration, email verification, login,
 * logout, and initial store setup.
 *
 * All POST routes are protected by CsrfMiddleware (enforced in routes.php).
 * store_id is NEVER accepted from request body — always from session.
 */
class StoreController
{
    private StoreService $service;

    public function __construct()
    {
        $this->service = new StoreService();
    }

    // -----------------------------------------------------------------------
    // Registration
    // -----------------------------------------------------------------------

    /**
     * GET /register — Show the registration form.
     */
    public function showRegister(Request $request): void
    {
        if ($this->isLoggedIn()) {
            Response::redirect('/dashboard');
        }

        Response::view('layouts/auth', [
            'pageTitle' => 'Register — Kinara Store Hub',
            'view'      => $this->viewPath('auth/register'),
        ]);
    }

    /**
     * POST /register — Process the registration form.
     */
    public function register(Request $request): void
    {
        $data = [
            'store_name' => $request->post('store_name', ''),
            'owner_name' => $request->post('owner_name', ''),
            'email'      => $request->post('email', ''),
            'mobile'     => $request->post('mobile', ''),
            'password'   => $request->post('password', ''),
        ];

        $result = $this->service->register($data);

        if (!$result['success']) {
            $this->flashErrors($result['errors']);

            Response::view('layouts/auth', [
                'pageTitle' => 'Register — Kinara Store Hub',
                'view'      => $this->viewPath('auth/register'),
                'old'       => $data,
                'errors'    => $result['errors'],
            ]);
        }

        // Success — show check-email page.
        $this->flash('success', 'Registration successful! Please check your email to verify your account.');

        Response::view('layouts/auth', [
            'pageTitle' => 'Verify Your Email — Kinara Store Hub',
            'view'      => $this->viewPath('auth/verify-pending'),
            'email'     => $data['email'],
        ]);
    }

    // -----------------------------------------------------------------------
    // Email verification
    // -----------------------------------------------------------------------

    /**
     * GET /verify/:token — Verify email and activate the store.
     */
    public function verifyEmail(Request $request): void
    {
        $token = $request->params['token'] ?? '';

        if ($token === '') {
            Response::redirect('/login');
        }

        $result = $this->service->verifyEmail($token);

        if (!$result['success']) {
            $this->flash('error', $result['error'] ?? 'Verification failed.');
            Response::redirect('/login');
        }

        // Log the owner in immediately after verification.
        $this->setSession(
            $result['store_id'],
            $result['staff_id'],
            $result['role_id'],
            '',
            ''
        );

        // Load store info for session.
        $storeModel = new \App\Models\StoreModel();
        $store = $storeModel->findById($result['store_id']);

        if ($store !== null) {
            $_SESSION['store_name'] = $store['name'];
            $_SESSION['staff_name'] = $store['owner_name'];
            $_SESSION['store_logo'] = $store['logo_path'] ?? '';
        }

        $this->flash('success', 'Email verified successfully! Please complete your store setup.');

        Response::redirect('/setup');
    }

    // -----------------------------------------------------------------------
    // Login / Logout
    // -----------------------------------------------------------------------

    /**
     * GET /login — Show the login form.
     */
    public function showLogin(Request $request): void
    {
        if ($this->isLoggedIn()) {
            Response::redirect('/dashboard');
        }

        Response::view('layouts/auth', [
            'pageTitle' => 'Login — Kinara Store Hub',
            'view'      => $this->viewPath('auth/login'),
        ]);
    }

    /**
     * POST /login — Authenticate the user.
     */
    public function login(Request $request): void
    {
        $email    = (string) $request->post('email', '');
        $password = (string) $request->post('password', '');

        $result = $this->service->login($email, $password);

        if (!$result['success']) {
            $this->flash('error', $result['error']);

            Response::view('layouts/auth', [
                'pageTitle'    => 'Login — Kinara Store Hub',
                'view'         => $this->viewPath('auth/login'),
                'old'          => ['email' => $email],
                'errorMessage' => $result['error'],
            ]);
        }

        $staff = $result['staff'];

        // Regenerate session ID to prevent session fixation.
        session_regenerate_id(true);

        $this->setSession(
            (int) $staff['store_id'],
            (int) $staff['id'],
            (int) $staff['role_id'],
            $staff['store_name'] ?? '',
            $staff['name'] ?? ''
        );

        // Load store logo.
        $storeModel = new \App\Models\StoreModel();
        $store = $storeModel->findById((int) $staff['store_id']);
        $_SESSION['store_logo'] = $store['logo_path'] ?? '';

        Response::redirect('/dashboard');
    }

    /**
     * POST /logout — Destroy the session and redirect to login.
     */
    public function logout(Request $request): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        Response::redirect('/login');
    }

    // -----------------------------------------------------------------------
    // Store setup (post-verification)
    // -----------------------------------------------------------------------

    /**
     * GET /setup — Show the store setup form.
     */
    public function showSetup(Request $request): void
    {
        $storeName = $_SESSION['store_name'] ?? '';

        Response::view('layouts/auth', [
            'pageTitle' => 'Store Setup — Kinara Store Hub',
            'view'      => $this->viewPath('auth/setup'),
            'storeName' => $storeName,
        ]);
    }

    /**
     * POST /setup — Save store setup data.
     */
    public function saveSetup(Request $request): void
    {
        $storeId = $request->storeId;

        $data = [
            'address_street'  => $request->post('address_street', ''),
            'address_city'    => $request->post('address_city', ''),
            'address_state'   => $request->post('address_state', ''),
            'address_pincode' => $request->post('address_pincode', ''),
        ];

        $logoFile = $request->file('logo');

        $result = $this->service->saveSetup($storeId, $data, $logoFile);

        if (!$result['success']) {
            $this->flashErrors($result['errors']);

            Response::view('layouts/auth', [
                'pageTitle' => 'Store Setup — Kinara Store Hub',
                'view'      => $this->viewPath('auth/setup'),
                'old'       => $data,
                'errors'    => $result['errors'],
                'storeName' => $_SESSION['store_name'] ?? '',
            ]);
        }

        // Update session with logo if uploaded.
        $storeModel = new \App\Models\StoreModel();
        $store = $storeModel->findById($storeId);
        if ($store !== null) {
            $_SESSION['store_logo'] = $store['logo_path'] ?? '';
            $_SESSION['store_name'] = $store['name'];
        }

        $this->flash('success', 'Store setup complete! Welcome to your dashboard.');

        Response::redirect('/dashboard');
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Resolve a view name to an absolute filesystem path.
     */
    private function viewPath(string $name): string
    {
        return dirname(__DIR__, 2) . '/views/' . $name . '.php';
    }

    /**
     * Check if the current session has an authenticated user.
     */
    private function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return !empty($_SESSION['store_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Set session variables for an authenticated user.
     */
    private function setSession(int $storeId, int $staffId, int $roleId, string $storeName, string $staffName): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['store_id']   = $storeId;
        $_SESSION['user_id']    = $staffId;
        $_SESSION['role_id']    = $roleId;
        $_SESSION['store_name'] = $storeName;
        $_SESSION['staff_name'] = $staffName;
    }

    /**
     * Add a flash message to the session.
     */
    private function flash(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }

        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Flash an array of error messages.
     *
     * @param string[] $errors
     */
    private function flashErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->flash('error', $error);
        }
    }
}
