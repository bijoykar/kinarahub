<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\CustomerService;

/**
 * CustomerApiController — REST API for customers and credit management.
 *
 * Reuses CustomerService for all business logic.
 */
class CustomerApiController
{
    private CustomerService $service;

    public function __construct()
    {
        $this->service = new CustomerService();
    }

    /**
     * GET /customers — Paginated customer listing.
     *
     * Query params: page, per_page, search
     */
    public function index(Request $request): void
    {
        $page    = max(1, (int) ($request->get('page') ?? 1));
        $perPage = min(100, max(1, (int) ($request->get('per_page') ?? 20)));
        $search  = (string) ($request->get('search') ?? '');

        $data = $this->service->listCustomers($request->storeId, $page, $perPage, $search);

        $totalPages = max(1, (int) ceil($data['total'] / $perPage));

        Response::json([
            'success' => true,
            'data'    => $data['customers'],
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
     * POST /customers — Create a new customer.
     */
    public function store(Request $request): void
    {
        $body = $request->all();

        $result = $this->service->createCustomer($request->storeId, [
            'name'   => (string) ($body['name'] ?? ''),
            'mobile' => (string) ($body['mobile'] ?? ''),
            'email'  => (string) ($body['email'] ?? ''),
        ]);

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
            'data'    => ['customer_id' => $result['customer_id']],
            'meta'    => null,
            'error'   => null,
        ], 201);
    }

    /**
     * GET /customers/:id/credits — Credit history for a customer.
     */
    public function credits(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);
        $detail = $this->service->getCustomerDetail($id, $request->storeId);

        if ($detail === null) {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Customer not found.',
            ], 404);
        }

        Response::json([
            'success' => true,
            'data'    => [
                'customer'        => $detail['customer'],
                'credits'         => $detail['credits'],
                'payment_history' => $detail['payments'],
            ],
            'meta'  => null,
            'error' => null,
        ]);
    }

    /**
     * POST /customers/:id/payments — Record a payment for a customer.
     */
    public function recordPayment(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);
        $body = $request->all();

        $result = $this->service->recordPayment($request->storeId, $id, [
            'amount'         => (string) ($body['amount'] ?? '0'),
            'payment_method' => (string) ($body['payment_method'] ?? ''),
            'notes'          => (string) ($body['notes'] ?? ''),
        ]);

        if (!$result['success']) {
            Response::json([
                'success' => false,
                'data'    => ['errors' => $result['errors']],
                'meta'    => null,
                'error'   => $result['errors'][0] ?? 'Payment failed.',
            ], 422);
        }

        Response::json([
            'success' => true,
            'data'    => ['message' => 'Payment recorded successfully.'],
            'meta'    => null,
            'error'   => null,
        ], 201);
    }
}
