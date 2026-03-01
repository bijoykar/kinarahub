<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\PermissionMiddleware;
use App\Services\ProductService;

/**
 * ProductController — Inventory management: list, create, update, deactivate products.
 * Also handles CSV import/export.
 */
class ProductController
{
    private ProductService $service;

    public function __construct()
    {
        $this->service = new ProductService();
    }

    /**
     * GET /inventory — List products with search, filter, pagination.
     */
    public function index(Request $request): void
    {
        $page       = max(1, (int) ($request->get('page') ?? 1));
        $perPage    = defined('PER_PAGE') ? PER_PAGE : 25;
        $search     = (string) ($request->get('search') ?? '');
        $categoryId = (int) ($request->get('category_id') ?? 0);
        $status     = (string) ($request->get('status') ?? '');

        $data = $this->service->listProducts($request->storeId, $page, $perPage, $search, $categoryId, $status);

        $totalPages = max(1, (int) ceil($data['total'] / $perPage));

        // Check if cost_price should be hidden for this role.
        $pdo = require dirname(__DIR__, 2) . '/config/db.php';
        $hiddenFields = PermissionMiddleware::getRestrictedFields($request->roleId, $pdo);
        $hideCostPrice = in_array('cost_price', $hiddenFields, true);

        Response::view('layouts/app', [
            'pageTitle'     => 'Inventory — Kinara Store Hub',
            'breadcrumb'    => [['label' => 'Inventory']],
            'view'          => $this->viewPath('inventory/index'),
            'products'      => $data['products'],
            'categories'    => $data['categories'],
            'units'         => $data['units'],
            'hideCostPrice' => $hideCostPrice,
            'pagination'    => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $data['total'],
                'total_pages' => $totalPages,
            ],
            'filters' => [
                'search'      => $search,
                'category_id' => $categoryId,
                'status'      => $status,
            ],
        ]);
    }

    /**
     * POST /inventory — Create a new product.
     */
    public function store(Request $request): void
    {
        $data = [
            'sku'            => (string) $request->post('sku', ''),
            'name'           => (string) $request->post('name', ''),
            'category_id'    => (int) $request->post('category_id', 0),
            'uom_id'         => (int) $request->post('uom_id', 0),
            'selling_price'  => (float) $request->post('selling_price', 0),
            'cost_price'     => (float) $request->post('cost_price', 0),
            'stock_quantity' => (float) $request->post('stock_quantity', 0),
            'reorder_point'  => (float) $request->post('reorder_point', 0),
            'status'         => 'active',
        ];

        $result = $this->service->createProduct($request->storeId, $data);

        if (!$result['success']) {
            $this->flashErrors($result['errors']);
        } else {
            $this->flash('success', 'Product created successfully.');
        }

        Response::redirect('/inventory');
    }

    /**
     * POST /inventory/:id — Update a product (optimistic locking via version field).
     */
    public function update(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);
        $expectedVersion = (int) $request->post('version', 0);

        $data = [
            'sku'            => (string) $request->post('sku', ''),
            'name'           => (string) $request->post('name', ''),
            'category_id'    => (int) $request->post('category_id', 0),
            'uom_id'         => (int) $request->post('uom_id', 0),
            'selling_price'  => (float) $request->post('selling_price', 0),
            'cost_price'     => (float) $request->post('cost_price', 0),
            'stock_quantity' => (float) $request->post('stock_quantity', 0),
            'reorder_point'  => (float) $request->post('reorder_point', 0),
            'status'         => (string) $request->post('status', 'active'),
        ];

        $result = $this->service->updateProduct($id, $request->storeId, $data, $expectedVersion);

        if (!$result['success']) {
            if ($result['conflict']) {
                http_response_code(409);
            }
            $this->flashErrors($result['errors']);
        } else {
            $this->flash('success', 'Product updated successfully.');
        }

        Response::redirect('/inventory');
    }

    /**
     * POST /inventory/:id/delete — Deactivate a product.
     */
    public function destroy(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);

        $this->service->deactivateProduct($id, $request->storeId);
        $this->flash('success', 'Product deactivated successfully.');

        Response::redirect('/inventory');
    }

    /**
     * POST /inventory/import — Import products from CSV.
     */
    public function import(Request $request): void
    {
        $file = $request->file('csv_file');

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please select a valid CSV file.');
            Response::redirect('/inventory');
        }

        // Validate file type.
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $this->flash('error', 'Only CSV files are accepted.');
            Response::redirect('/inventory');
        }

        $result = $this->service->importCsv($request->storeId, $file['tmp_name']);

        $message = "Import complete: {$result['inserted']} inserted, {$result['updated']} updated, {$result['failed']} failed.";
        $this->flash($result['failed'] > 0 ? 'warning' : 'success', $message);

        if (!empty($result['errors'])) {
            foreach (array_slice($result['errors'], 0, 5) as $error) {
                $this->flash('error', $error);
            }
        }

        Response::redirect('/inventory');
    }

    /**
     * GET /inventory/export — Export products as CSV download.
     */
    public function export(Request $request): void
    {
        $data = $this->service->exportCsv($request->storeId);

        $storeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $_SESSION['store_name'] ?? 'store');
        $filename = "inventory_{$storeName}_" . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Write header row.
        fputcsv($output, $data['headers']);

        // Write data rows.
        foreach ($data['rows'] as $row) {
            fputcsv($output, array_values($row));
        }

        fclose($output);
        exit;
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
