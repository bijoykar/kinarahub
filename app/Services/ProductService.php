<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\CsvParser;
use App\Models\CategoryModel;
use App\Models\ProductModel;

/**
 * ProductService — Business logic for inventory/product management.
 */
class ProductService
{
    private ProductModel $productModel;
    private CategoryModel $categoryModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }

    /**
     * List products with pagination, search, and filters.
     *
     * @return array{products: array, total: int, categories: array, units: array}
     */
    public function listProducts(int $storeId, int $page, int $perPage, string $search = '', int $categoryId = 0, string $status = ''): array
    {
        $result = $this->productModel->listPaginated($storeId, $page, $perPage, $search, $categoryId, $status);
        $categories = $this->categoryModel->listForStore($storeId);
        $units = $this->productModel->listUnitsOfMeasure();

        return [
            'products'   => $result['products'],
            'total'      => $result['total'],
            'categories' => $categories,
            'units'      => $units,
        ];
    }

    /**
     * Get a single product by ID.
     *
     * @return array<string, mixed>|null
     */
    public function getProduct(int $id, int $storeId): ?array
    {
        return $this->productModel->findById($id, $storeId);
    }

    /**
     * Create a new product.
     *
     * @return array{success: bool, errors: string[], product_id: int|null}
     */
    public function createProduct(int $storeId, array $data): array
    {
        $errors = $this->validateProduct($data, $storeId);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'product_id' => null];
        }

        // Check SKU uniqueness within store.
        $existing = $this->productModel->findBySku($data['sku'], $storeId);
        if ($existing !== null) {
            return ['success' => false, 'errors' => ['A product with this SKU already exists.'], 'product_id' => null];
        }

        $productId = $this->productModel->create($storeId, $data);

        return ['success' => true, 'errors' => [], 'product_id' => $productId];
    }

    /**
     * Update an existing product with optimistic locking.
     *
     * @return array{success: bool, errors: string[], conflict: bool}
     */
    public function updateProduct(int $id, int $storeId, array $data, int $expectedVersion): array
    {
        $errors = $this->validateProduct($data, $storeId, $id);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'conflict' => false];
        }

        // Check SKU uniqueness (excluding self).
        if (isset($data['sku'])) {
            $existing = $this->productModel->findBySku($data['sku'], $storeId);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                return ['success' => false, 'errors' => ['A product with this SKU already exists.'], 'conflict' => false];
            }
        }

        $updated = $this->productModel->update($id, $storeId, $data, $expectedVersion);

        if (!$updated) {
            return ['success' => false, 'errors' => ['The product was modified by another user. Please refresh and try again.'], 'conflict' => true];
        }

        return ['success' => true, 'errors' => [], 'conflict' => false];
    }

    /**
     * Deactivate a product (soft delete).
     */
    public function deactivateProduct(int $id, int $storeId): void
    {
        $this->productModel->deactivate($id, $storeId);
    }

    // -----------------------------------------------------------------------
    // CSV Import
    // -----------------------------------------------------------------------

    /**
     * Import products from a CSV file.
     * Upserts on SKU: updates all fields if SKU exists, inserts if new.
     *
     * @param int    $storeId
     * @param string $filePath Temporary path to the uploaded CSV file.
     * @return array{inserted: int, updated: int, failed: int, errors: array<int, string>}
     */
    public function importCsv(int $storeId, string $filePath): array
    {
        $rows = CsvParser::parse($filePath);

        $inserted = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because header is row 1, data starts row 2

            // Required: sku, name
            if (empty($row['sku']) || empty($row['name'])) {
                $errors[$rowNum] = "Row {$rowNum}: SKU and name are required.";
                $failed++;
                continue;
            }

            // Validate numeric fields.
            $numericFields = ['selling_price', 'cost_price', 'stock_quantity', 'reorder_point'];
            $hasNumericError = false;
            foreach ($numericFields as $field) {
                if (isset($row[$field]) && $row[$field] !== '' && !is_numeric($row[$field])) {
                    $errors[$rowNum] = "Row {$rowNum}: '{$field}' must be a valid number.";
                    $failed++;
                    $hasNumericError = true;
                    break;
                }
            }
            if ($hasNumericError) {
                continue;
            }

            // Resolve category (find or create).
            $categoryId = null;
            if (!empty($row['category'])) {
                $categoryId = $this->categoryModel->findOrCreate($storeId, trim($row['category']));
            }

            // Resolve UOM.
            $uomId = null;
            if (!empty($row['uom'])) {
                $uom = $this->productModel->findUomByName(trim($row['uom']));
                if ($uom !== null) {
                    $uomId = (int) $uom['id'];
                }
            }

            $productData = [
                'sku'            => trim($row['sku']),
                'name'           => trim($row['name']),
                'category_id'    => $categoryId,
                'uom_id'         => $uomId,
                'selling_price'  => (float) ($row['selling_price'] ?? 0),
                'cost_price'     => (float) ($row['cost_price'] ?? 0),
                'stock_quantity' => (float) ($row['stock_quantity'] ?? 0),
                'reorder_point'  => (float) ($row['reorder_point'] ?? 0),
                'status'         => 'active',
            ];

            try {
                $action = $this->productModel->upsertBySku($storeId, $productData);
                if ($action === 'inserted') {
                    $inserted++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $errors[$rowNum] = "Row {$rowNum}: " . $e->getMessage();
                $failed++;
            }
        }

        return [
            'inserted' => $inserted,
            'updated'  => $updated,
            'failed'   => $failed,
            'errors'   => $errors,
        ];
    }

    // -----------------------------------------------------------------------
    // CSV Export
    // -----------------------------------------------------------------------

    /**
     * Export all products for a store as CSV data.
     *
     * @return array{headers: string[], rows: array<int, array<string, mixed>>}
     */
    public function exportCsv(int $storeId): array
    {
        $products = $this->productModel->allForExport($storeId);
        $headers = ['sku', 'name', 'category', 'uom', 'selling_price', 'cost_price', 'stock_quantity', 'reorder_point', 'status'];

        return ['headers' => $headers, 'rows' => $products];
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function validateProduct(array $data, int $storeId, int $excludeId = 0): array
    {
        $errors = [];

        if (empty($data['sku']) || strlen(trim($data['sku'])) < 1) {
            $errors[] = 'SKU is required.';
        }

        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            $errors[] = 'Product name is required (minimum 2 characters).';
        }

        if (isset($data['selling_price']) && (!is_numeric($data['selling_price']) || (float) $data['selling_price'] < 0)) {
            $errors[] = 'Selling price must be a positive number.';
        }

        if (isset($data['cost_price']) && (!is_numeric($data['cost_price']) || (float) $data['cost_price'] < 0)) {
            $errors[] = 'Cost price must be a positive number.';
        }

        if (isset($data['stock_quantity']) && (!is_numeric($data['stock_quantity']) || (float) $data['stock_quantity'] < 0)) {
            $errors[] = 'Stock quantity must be a positive number.';
        }

        return $errors;
    }
}
