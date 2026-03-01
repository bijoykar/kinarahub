<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CategoryModel;

/**
 * CategoryService — Business logic for product categories.
 */
class CategoryService
{
    private CategoryModel $model;

    public function __construct()
    {
        $this->model = new CategoryModel();
    }

    /**
     * List all categories for a store.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function listCategories(int $storeId): array
    {
        return $this->model->listForStore($storeId);
    }

    /**
     * Create a new category.
     *
     * @return array{success: bool, errors: string[], category_id: int|null}
     */
    public function createCategory(int $storeId, string $name): array
    {
        $name = trim($name);

        if ($name === '' || strlen($name) < 2) {
            return ['success' => false, 'errors' => ['Category name is required (minimum 2 characters).'], 'category_id' => null];
        }

        $existing = $this->model->findByName($name, $storeId);
        if ($existing !== null) {
            return ['success' => false, 'errors' => ['A category with this name already exists.'], 'category_id' => null];
        }

        $id = $this->model->create($storeId, $name);

        return ['success' => true, 'errors' => [], 'category_id' => $id];
    }

    /**
     * Update a category.
     *
     * @return array{success: bool, errors: string[]}
     */
    public function updateCategory(int $id, int $storeId, string $name): array
    {
        $name = trim($name);

        if ($name === '' || strlen($name) < 2) {
            return ['success' => false, 'errors' => ['Category name is required (minimum 2 characters).']];
        }

        $existing = $this->model->findByName($name, $storeId);
        if ($existing !== null && (int) $existing['id'] !== $id) {
            return ['success' => false, 'errors' => ['A category with this name already exists.']];
        }

        $this->model->update($id, $storeId, $name);

        return ['success' => true, 'errors' => []];
    }

    /**
     * Delete a category.
     *
     * @return array{success: bool, error: string|null}
     */
    public function deleteCategory(int $id, int $storeId): array
    {
        $deleted = $this->model->delete($id, $storeId);

        if (!$deleted) {
            return ['success' => false, 'error' => 'Cannot delete a category that has products assigned.'];
        }

        return ['success' => true, 'error' => null];
    }
}
