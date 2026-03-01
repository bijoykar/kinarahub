<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\CategoryService;

/**
 * CategoryController — CRUD for product categories.
 */
class CategoryController
{
    private CategoryService $service;

    public function __construct()
    {
        $this->service = new CategoryService();
    }

    /**
     * POST /inventory/categories — Create a new category.
     */
    public function store(Request $request): void
    {
        $name = (string) $request->post('name', '');

        $result = $this->service->createCategory($request->storeId, $name);

        if (!$result['success']) {
            $this->flashErrors($result['errors']);
        } else {
            $this->flash('success', 'Category created successfully.');
        }

        Response::redirect('/inventory');
    }

    /**
     * POST /inventory/categories/:id — Update a category.
     */
    public function update(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);
        $name = (string) $request->post('name', '');

        $result = $this->service->updateCategory($id, $request->storeId, $name);

        if (!$result['success']) {
            $this->flashErrors($result['errors']);
        } else {
            $this->flash('success', 'Category updated successfully.');
        }

        Response::redirect('/inventory');
    }

    /**
     * POST /inventory/categories/:id/delete — Delete a category.
     */
    public function destroy(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);

        $result = $this->service->deleteCategory($id, $request->storeId);

        if (!$result['success']) {
            $this->flash('error', $result['error']);
        } else {
            $this->flash('success', 'Category deleted successfully.');
        }

        Response::redirect('/inventory');
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

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
