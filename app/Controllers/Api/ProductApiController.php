<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\ProductService;

/**
 * ProductApiController — REST API for products.
 *
 * Reuses ProductService for all business logic.
 */
class ProductApiController
{
    private ProductService $service;

    public function __construct()
    {
        $this->service = new ProductService();
    }

    /**
     * GET /products — Paginated product listing with filters.
     *
     * Query params: page, per_page, search, category_id, status
     */
    public function index(Request $request): void
    {
        $page       = max(1, (int) ($request->get('page') ?? 1));
        $perPage    = min(100, max(1, (int) ($request->get('per_page') ?? 20)));
        $search     = (string) ($request->get('search') ?? '');
        $categoryId = (int) ($request->get('category_id') ?? 0);
        $status     = (string) ($request->get('status') ?? '');

        $data = $this->service->listProducts($request->storeId, $page, $perPage, $search, $categoryId, $status);

        $totalPages = max(1, (int) ceil($data['total'] / $perPage));

        Response::json([
            'success' => true,
            'data'    => $data['products'],
            'meta'    => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $data['total'],
                'total_pages' => $totalPages,
            ],
            'error' => null,
        ]);
    }

    /**
     * GET /products/:id — Single product detail.
     */
    public function show(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);
        $product = $this->service->getProduct($id, $request->storeId);

        if ($product === null) {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Product not found.',
            ], 404);
        }

        Response::json([
            'success' => true,
            'data'    => $product,
            'meta'    => null,
            'error'   => null,
        ]);
    }

    /**
     * POST /products — Create a new product.
     */
    public function store(Request $request): void
    {
        $data = $request->all();

        $result = $this->service->createProduct($request->storeId, $data);

        if (!$result['success']) {
            Response::json([
                'success' => false,
                'data'    => ['errors' => $result['errors']],
                'meta'    => null,
                'error'   => $result['errors'][0] ?? 'Validation failed.',
            ], 422);
        }

        Response::json([
            'success' => true,
            'data'    => ['product_id' => $result['product_id']],
            'meta'    => null,
            'error'   => null,
        ], 201);
    }

    /**
     * PUT /products/:id — Update a product.
     */
    public function update(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);
        $data = $request->all();
        $version = (int) ($data['version'] ?? 0);

        $result = $this->service->updateProduct($id, $request->storeId, $data, $version);

        if (!$result['success']) {
            $code = ($result['conflict'] ?? false) ? 409 : 422;
            Response::json([
                'success' => false,
                'data'    => ['errors' => $result['errors']],
                'meta'    => null,
                'error'   => $result['errors'][0] ?? 'Update failed.',
            ], $code);
        }

        Response::json([
            'success' => true,
            'data'    => ['message' => 'Product updated successfully.'],
            'meta'    => null,
            'error'   => null,
        ]);
    }

    /**
     * DELETE /products/:id — Deactivate a product (soft delete).
     */
    public function destroy(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);

        $this->service->deactivateProduct($id, $request->storeId);

        Response::json([
            'success' => true,
            'data'    => ['message' => 'Product deactivated successfully.'],
            'meta'    => null,
            'error'   => null,
        ]);
    }
}
