<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\SaleService;

/**
 * SaleController — POS, bookkeeping entry, and sales history.
 *
 * Sale creation is atomic: see SaleService for the full transaction flow.
 */
class SaleController
{
    private SaleService $service;

    public function __construct()
    {
        $this->service = new SaleService();
    }

    // -----------------------------------------------------------------------
    // Sales history
    // -----------------------------------------------------------------------

    /**
     * GET /sales — Sales history listing with search and filters.
     */
    public function index(Request $request): void
    {
        $page          = max(1, (int) ($request->get('page') ?? 1));
        $perPage       = defined('PER_PAGE') ? PER_PAGE : 25;
        $search        = (string) ($request->get('search') ?? '');
        $from          = (string) ($request->get('from') ?? '');
        $to            = (string) ($request->get('to') ?? '');
        $paymentMethod = (string) ($request->get('payment_method') ?? '');

        $data = $this->service->listSales($request->storeId, $page, $perPage, $search, $from, $to, $paymentMethod);

        $totalPages = max(1, (int) ceil($data['total'] / $perPage));

        Response::view('layouts/app', [
            'pageTitle'  => 'Sales History — Kinara Store Hub',
            'breadcrumb' => [['label' => 'Sales History']],
            'view'       => $this->viewPath('sales/index'),
            'sales'      => $data['sales'],
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $data['total'],
                'total_pages' => $totalPages,
            ],
            'filters' => [
                'search'         => $search,
                'from'           => $from,
                'to'             => $to,
                'payment_method' => $paymentMethod,
            ],
        ]);
    }

    /**
     * GET /sales/:id — Sale detail page.
     */
    public function show(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);
        $sale = $this->service->getSale($id, $request->storeId);

        if ($sale === null) {
            http_response_code(404);
            require dirname(__DIR__, 2) . '/views/errors/404.php';
            exit;
        }

        Response::view('layouts/app', [
            'pageTitle'  => 'Sale ' . $sale['sale_number'] . ' — Kinara Store Hub',
            'breadcrumb' => [
                ['label' => 'Sales History', 'url' => '/kinarahub/sales'],
                ['label' => $sale['sale_number']],
            ],
            'view' => $this->viewPath('sales/detail'),
            'sale' => $sale,
        ]);
    }

    // -----------------------------------------------------------------------
    // POS
    // -----------------------------------------------------------------------

    /**
     * GET /pos — Show the POS terminal.
     */
    public function pos(Request $request): void
    {
        $products  = $this->service->getProductsForPos($request->storeId);
        $customers = $this->service->getCustomers($request->storeId);

        Response::view('layouts/app', [
            'pageTitle'  => 'POS — Kinara Store Hub',
            'breadcrumb' => [['label' => 'POS / New Sale']],
            'view'       => $this->viewPath('sales/pos'),
            'products'   => $products,
            'customers'  => $customers,
        ]);
    }

    // -----------------------------------------------------------------------
    // Bookkeeping
    // -----------------------------------------------------------------------

    /**
     * GET /sales/bookkeeping — Show the bookkeeping entry form.
     */
    public function bookkeeping(Request $request): void
    {
        $products  = $this->service->getProductsForPos($request->storeId);
        $customers = $this->service->getCustomers($request->storeId);

        Response::view('layouts/app', [
            'pageTitle'  => 'Bookkeeping Entry — Kinara Store Hub',
            'breadcrumb' => [
                ['label' => 'Sales History', 'url' => '/kinarahub/sales'],
                ['label' => 'Bookkeeping Entry'],
            ],
            'view'      => $this->viewPath('sales/bookkeeping'),
            'products'  => $products,
            'customers' => $customers,
        ]);
    }

    // -----------------------------------------------------------------------
    // Create sale (shared by POS and bookkeeping)
    // -----------------------------------------------------------------------

    /**
     * POST /sales — Create a new sale.
     *
     * Accepts items as JSON-encoded array in 'items' field,
     * or as individual form fields items[0][product_id], items[0][quantity], etc.
     */
    public function store(Request $request): void
    {
        $entryMode     = (string) $request->post('entry_mode', 'pos');
        $saleDate      = (string) $request->post('sale_date', date('Y-m-d'));
        $customerId    = (int) $request->post('customer_id', 0);
        $paymentMethod = (string) $request->post('payment_method', '');
        $notes         = (string) $request->post('notes', '');

        // Parse items.
        $items = $this->parseItems($request);

        $saleData = [
            'entry_mode'     => $entryMode,
            'sale_date'      => $saleDate,
            'customer_id'    => $customerId ?: null,
            'payment_method' => $paymentMethod,
            'notes'          => $notes,
        ];

        $result = $this->service->createSale(
            $request->storeId,
            $request->staffId,
            $saleData,
            $items
        );

        if (!$result['success']) {
            if ($result['conflict']) {
                http_response_code(409);
            }
            $this->flashErrors($result['errors']);

            // Redirect back to the entry mode page.
            if ($entryMode === 'booking') {
                Response::redirect('/sales/bookkeeping');
            } else {
                Response::redirect('/pos');
            }
        }

        $this->flash('success', 'Sale recorded successfully.');
        Response::redirect('/sales/' . $result['sale_id']);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Parse sale items from the request.
     * Supports both JSON-encoded 'items' field and form array format.
     *
     * @return array<int, array{product_id: int, variant_id: int, quantity: float, unit_price: float}>
     */
    private function parseItems(Request $request): array
    {
        // Try JSON-encoded items first (POS sends JSON).
        $jsonItems = $request->post('items_json', '');
        if (!empty($jsonItems)) {
            $decoded = json_decode($jsonItems, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Fall back to form array items[0][product_id], items[0][quantity], etc.
        $rawItems = $_POST['items'] ?? [];
        $items = [];

        if (is_array($rawItems)) {
            foreach ($rawItems as $raw) {
                if (empty($raw['product_id'])) {
                    continue;
                }
                $items[] = [
                    'product_id' => (int) ($raw['product_id'] ?? 0),
                    'variant_id' => (int) ($raw['variant_id'] ?? 0),
                    'quantity'   => (float) ($raw['quantity'] ?? 0),
                    'unit_price' => (float) ($raw['unit_price'] ?? 0),
                ];
            }
        }

        return $items;
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
