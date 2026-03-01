<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\SaleService;

/**
 * SaleApiController — REST API for sales.
 *
 * Reuses SaleService for all business logic.
 */
class SaleApiController
{
    private SaleService $service;

    public function __construct()
    {
        $this->service = new SaleService();
    }

    /**
     * GET /sales — Paginated sales listing with filters.
     *
     * Query params: page, per_page, search, from, to, payment_method
     */
    public function index(Request $request): void
    {
        $page          = max(1, (int) ($request->get('page') ?? 1));
        $perPage       = min(100, max(1, (int) ($request->get('per_page') ?? 20)));
        $search        = (string) ($request->get('search') ?? '');
        $from          = (string) ($request->get('from') ?? '');
        $to            = (string) ($request->get('to') ?? '');
        $paymentMethod = (string) ($request->get('payment_method') ?? '');

        $data = $this->service->listSales($request->storeId, $page, $perPage, $search, $from, $to, $paymentMethod);

        $totalPages = max(1, (int) ceil($data['total'] / $perPage));

        Response::json([
            'success' => true,
            'data'    => $data['sales'],
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
     * GET /sales/:id — Single sale detail with items.
     */
    public function show(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);
        $sale = $this->service->getSale($id, $request->storeId);

        if ($sale === null) {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Sale not found.',
            ], 404);
        }

        Response::json([
            'success' => true,
            'data'    => $sale,
            'meta'    => null,
            'error'   => null,
        ]);
    }

    /**
     * POST /sales — Create a new sale.
     *
     * JSON body: { entry_mode, sale_date, customer_id, payment_method, notes, items: [{product_id, variant_id, quantity, unit_price}] }
     */
    public function store(Request $request): void
    {
        $body = $request->all();

        $saleData = [
            'entry_mode'     => (string) ($body['entry_mode'] ?? 'pos'),
            'sale_date'      => (string) ($body['sale_date'] ?? date('Y-m-d')),
            'customer_id'    => !empty($body['customer_id']) ? (int) $body['customer_id'] : null,
            'payment_method' => (string) ($body['payment_method'] ?? ''),
            'notes'          => (string) ($body['notes'] ?? ''),
        ];

        $items = $body['items'] ?? [];
        if (!is_array($items)) {
            $items = [];
        }

        $result = $this->service->createSale(
            $request->storeId,
            $request->staffId,
            $saleData,
            $items
        );

        if (!$result['success']) {
            $code = ($result['conflict'] ?? false) ? 409 : 422;
            Response::json([
                'success' => false,
                'data'    => ['errors' => $result['errors']],
                'meta'    => null,
                'error'   => $result['errors'][0] ?? 'Sale creation failed.',
            ], $code);
        }

        Response::json([
            'success' => true,
            'data'    => ['sale_id' => $result['sale_id']],
            'meta'    => null,
            'error'   => null,
        ], 201);
    }
}
