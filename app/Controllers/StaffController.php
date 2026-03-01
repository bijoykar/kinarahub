<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\StaffService;

/**
 * StaffController — CRUD for staff members within a store.
 *
 * All routes are authenticated and require 'settings' module permissions.
 */
class StaffController
{
    private StaffService $service;

    public function __construct()
    {
        $this->service = new StaffService();
    }

    /**
     * GET /settings/staff — List all staff members.
     */
    public function index(Request $request): void
    {
        $staff = $this->service->listStaff($request->storeId);
        $roles = $this->service->listRoles($request->storeId);

        Response::view('layouts/app', [
            'pageTitle'  => 'Staff — Kinara Store Hub',
            'breadcrumb' => [
                ['label' => 'Settings', 'url' => '/kinarahub/settings'],
                ['label' => 'Staff'],
            ],
            'view'  => $this->viewPath('settings/staff/index'),
            'staff' => $staff,
            'roles' => $roles,
        ]);
    }

    /**
     * POST /settings/staff — Create a new staff member.
     */
    public function store(Request $request): void
    {
        $data = [
            'name'     => (string) $request->post('name', ''),
            'email'    => (string) $request->post('email', ''),
            'mobile'   => (string) $request->post('mobile', ''),
            'password' => (string) $request->post('password', ''),
            'role_id'  => (int) $request->post('role_id', 0),
        ];

        $result = $this->service->createStaff($request->storeId, $data);

        if (!$result['success']) {
            $this->flashErrors($result['errors']);
        } else {
            $this->flash('success', 'Staff member created successfully.');
        }

        Response::redirect('/settings/staff');
    }

    /**
     * POST /settings/staff/:id — Update a staff member.
     */
    public function update(Request $request): void
    {
        $staffId = (int) ($request->params['id'] ?? 0);

        $data = [];
        $name = $request->post('name');
        if ($name !== null) {
            $data['name'] = (string) $name;
        }

        $email = $request->post('email');
        if ($email !== null) {
            $data['email'] = (string) $email;
        }

        $mobile = $request->post('mobile');
        if ($mobile !== null) {
            $data['mobile'] = (string) $mobile;
        }

        $roleId = $request->post('role_id');
        if ($roleId !== null) {
            $data['role_id'] = (int) $roleId;
        }

        $password = $request->post('password');
        if (!empty($password)) {
            $data['password'] = (string) $password;
        }

        $result = $this->service->updateStaff($staffId, $request->storeId, $data);

        if (!$result['success']) {
            $this->flashErrors($result['errors']);
        } else {
            $this->flash('success', 'Staff member updated successfully.');
        }

        Response::redirect('/settings/staff');
    }

    /**
     * POST /settings/staff/:id/toggle — Toggle staff active/inactive status.
     */
    public function toggleStatus(Request $request): void
    {
        $staffId = (int) ($request->params['id'] ?? 0);
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);

        $result = $this->service->toggleStatus($staffId, $request->storeId, $currentUserId);

        if (!$result['success']) {
            $this->flash('error', $result['error']);
        } else {
            $statusLabel = $result['new_status'] === 'active' ? 'activated' : 'deactivated';
            $this->flash('success', "Staff member {$statusLabel} successfully.");
        }

        Response::redirect('/settings/staff');
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function viewPath(string $name): string
    {
        return dirname(__DIR__, 2) . '/views/' . $name . '.php';
    }

    private function flash(string $type, string $message): void
    {
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    private function flashErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->flash('error', $error);
        }
    }
}
