<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\RoleService;

/**
 * RoleController — CRUD for roles and their permission matrix.
 *
 * All routes are authenticated and require 'settings' module permissions.
 */
class RoleController
{
    private RoleService $service;

    public function __construct()
    {
        $this->service = new RoleService();
    }

    /**
     * GET /settings/roles — List all roles.
     */
    public function index(Request $request): void
    {
        $roles = $this->service->listRoles($request->storeId);

        Response::view('layouts/app', [
            'pageTitle'  => 'Roles — Kinara Store Hub',
            'breadcrumb' => [
                ['label' => 'Settings', 'url' => '/kinarahub/settings'],
                ['label' => 'Roles'],
            ],
            'view'  => $this->viewPath('settings/roles/index'),
            'roles' => $roles,
        ]);
    }

    /**
     * GET /settings/roles/create — Show the new role form.
     */
    public function create(Request $request): void
    {
        Response::view('layouts/app', [
            'pageTitle'  => 'Create Role — Kinara Store Hub',
            'breadcrumb' => [
                ['label' => 'Settings', 'url' => '/kinarahub/settings'],
                ['label' => 'Roles', 'url' => '/kinarahub/settings/roles'],
                ['label' => 'Create'],
            ],
            'view'              => $this->viewPath('settings/roles/edit'),
            'role'              => ['id' => 0, 'name' => '', 'description' => ''],
            'permissions'       => [],
            'fieldRestrictions' => [],
            'isNew'             => true,
        ]);
    }

    /**
     * POST /settings/roles — Store a new role.
     */
    public function store(Request $request): void
    {
        $name        = (string) $request->post('name', '');
        $description = (string) $request->post('description', '');
        $permissions  = $this->parsePermissionsFromRequest($request);
        $fieldRestrictions = $this->parseFieldRestrictionsFromRequest($request);

        $result = $this->service->createRole(
            $request->storeId,
            $name,
            $description,
            $permissions,
            $fieldRestrictions
        );

        if (!$result['success']) {
            $this->flashErrors($result['errors']);

            Response::view('layouts/app', [
                'pageTitle'  => 'Create Role — Kinara Store Hub',
                'breadcrumb' => [
                    ['label' => 'Settings', 'url' => '/kinarahub/settings'],
                    ['label' => 'Roles', 'url' => '/kinarahub/settings/roles'],
                    ['label' => 'Create'],
                ],
                'view'              => $this->viewPath('settings/roles/edit'),
                'role'              => ['id' => 0, 'name' => $name, 'description' => $description],
                'permissions'       => $permissions,
                'fieldRestrictions' => $fieldRestrictions,
                'isNew'             => true,
                'errors'            => $result['errors'],
            ]);
        }

        $this->flash('success', 'Role created successfully.');
        Response::redirect('/settings/roles');
    }

    /**
     * GET /settings/roles/:id/edit — Show the role edit form.
     */
    public function edit(Request $request): void
    {
        $roleId = (int) ($request->params['id'] ?? 0);
        $data = $this->service->getRoleWithPermissions($roleId, $request->storeId);

        if ($data === null) {
            http_response_code(404);
            require dirname(__DIR__, 2) . '/views/errors/404.php';
            exit;
        }

        Response::view('layouts/app', [
            'pageTitle'  => 'Edit Role — Kinara Store Hub',
            'breadcrumb' => [
                ['label' => 'Settings', 'url' => '/kinarahub/settings'],
                ['label' => 'Roles', 'url' => '/kinarahub/settings/roles'],
                ['label' => 'Edit'],
            ],
            'view'              => $this->viewPath('settings/roles/edit'),
            'role'              => $data['role'],
            'permissions'       => $data['permissions'],
            'fieldRestrictions' => $data['fieldRestrictions'],
            'isNew'             => false,
        ]);
    }

    /**
     * POST /settings/roles/:id — Update an existing role.
     */
    public function update(Request $request): void
    {
        $roleId      = (int) ($request->params['id'] ?? 0);
        $name        = (string) $request->post('name', '');
        $description = (string) $request->post('description', '');
        $permissions  = $this->parsePermissionsFromRequest($request);
        $fieldRestrictions = $this->parseFieldRestrictionsFromRequest($request);

        $result = $this->service->updateRole(
            $roleId,
            $request->storeId,
            $name,
            $description,
            $permissions,
            $fieldRestrictions
        );

        if (!$result['success']) {
            $this->flashErrors($result['errors']);
            Response::redirect('/settings/roles/' . $roleId . '/edit');
        }

        $this->flash('success', 'Role updated successfully.');
        Response::redirect('/settings/roles');
    }

    /**
     * POST /settings/roles/:id/delete — Delete a role.
     */
    public function destroy(Request $request): void
    {
        $roleId = (int) ($request->params['id'] ?? 0);

        $result = $this->service->deleteRole($roleId, $request->storeId);

        if (!$result['success']) {
            $this->flash('error', $result['error']);
        } else {
            $this->flash('success', 'Role deleted successfully.');
        }

        Response::redirect('/settings/roles');
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Parse the permission matrix from POST data.
     * Expected format: permissions[module][action] = "1"
     *
     * @return array<string, array<string, bool>>
     */
    private function parsePermissionsFromRequest(Request $request): array
    {
        $raw = $_POST['permissions'] ?? [];
        $permissions = [];

        $modules = ['inventory', 'sales', 'customers', 'reports', 'settings'];
        $actions = ['create', 'read', 'update', 'delete'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissions[$module][$action] = !empty($raw[$module][$action]);
            }
        }

        return $permissions;
    }

    /**
     * Parse field restrictions from POST data.
     * Expected format: field_restrictions[field_key] = "1"
     *
     * @return array<string, bool>
     */
    private function parseFieldRestrictionsFromRequest(Request $request): array
    {
        $raw = $_POST['field_restrictions'] ?? [];
        $restrictions = [];

        $validKeys = ['cost_price', 'profit_margin', 'store_financials'];

        foreach ($validKeys as $key) {
            $restrictions[$key] = !empty($raw[$key]);
        }

        return $restrictions;
    }

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
