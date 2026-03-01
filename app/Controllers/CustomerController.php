<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\CustomerService;

/**
 * CustomerController — Customer listing, detail, creation, and payment recording.
 */
class CustomerController
{
    private CustomerService $service;

    public function __construct()
    {
        $this->service = new CustomerService();
    }

    // -----------------------------------------------------------------------
    // Customer listing
    // -----------------------------------------------------------------------

    /**
     * GET /customers — Customer listing with search and pagination.
     */
    public function index(Request $request): void
    {
        $page    = max(1, (int) ($request->get('page') ?? 1));
        $perPage = defined('PER_PAGE') ? PER_PAGE : 25;
        $search  = (string) ($request->get('search') ?? '');

        $data = $this->service->listCustomers($request->storeId, $page, $perPage, $search);

        $totalPages = max(1, (int) ceil($data['total'] / $perPage));

        Response::view('layouts/app', [
            'pageTitle'  => 'Customers — Kinara Store Hub',
            'breadcrumb' => [['label' => 'Customers']],
            'view'       => $this->viewPath('customers/index'),
            'customers'  => $data['customers'],
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $data['total'],
                'total_pages' => $totalPages,
            ],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // Customer detail
    // -----------------------------------------------------------------------

    /**
     * GET /customers/:id — Customer detail with credit and payment history.
     */
    public function show(Request $request): void
    {
        $id = (int) ($request->params['id'] ?? 0);
        $detail = $this->service->getCustomerDetail($id, $request->storeId);

        if ($detail === null) {
            http_response_code(404);
            require dirname(__DIR__, 2) . '/views/errors/404.php';
            exit;
        }

        Response::view('layouts/app', [
            'pageTitle'  => $detail['customer']['name'] . ' — Kinara Store Hub',
            'breadcrumb' => [
                ['label' => 'Customers', 'url' => '/kinarahub/customers'],
                ['label' => $detail['customer']['name']],
            ],
            'view'     => $this->viewPath('customers/detail'),
            'customer' => $detail['customer'],
            'credits'  => $detail['credits'],
            'payments' => $detail['payments'],
        ]);
    }

    // -----------------------------------------------------------------------
    // Create customer
    // -----------------------------------------------------------------------

    /**
     * POST /customers — Create a new customer.
     */
    public function store(Request $request): void
    {
        $data = [
            'name'   => (string) $request->post('name', ''),
            'mobile' => (string) $request->post('mobile', ''),
            'email'  => (string) $request->post('email', ''),
        ];

        $result = $this->service->createCustomer($request->storeId, $data);

        if (!$result['success']) {
            $this->flashErrors($result['errors']);
            Response::redirect('/customers');
            return;
        }

        $this->flash('success', 'Customer added successfully.');
        Response::redirect('/customers');
    }

    // -----------------------------------------------------------------------
    // Record payment
    // -----------------------------------------------------------------------

    /**
     * POST /customers/:id/payments — Record a payment for a customer.
     */
    public function recordPayment(Request $request): void
    {
        $customerId = (int) ($request->params['id'] ?? 0);

        $data = [
            'amount'         => (string) $request->post('amount', '0'),
            'payment_method' => (string) $request->post('payment_method', ''),
            'notes'          => (string) $request->post('notes', ''),
        ];

        $result = $this->service->recordPayment($request->storeId, $customerId, $data);

        if (!$result['success']) {
            $this->flashErrors($result['errors']);
            Response::redirect('/customers/' . $customerId);
            return;
        }

        $this->flash('success', 'Payment recorded successfully.');
        Response::redirect('/customers/' . $customerId);
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
