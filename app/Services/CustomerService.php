<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomerModel;

/**
 * CustomerService — Business logic for customer and credit management.
 */
class CustomerService
{
    private CustomerModel $customerModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
    }

    /**
     * List customers with pagination and search.
     *
     * @return array{customers: array, total: int}
     */
    public function listCustomers(int $storeId, int $page, int $perPage, string $search = ''): array
    {
        return $this->customerModel->listPaginated($storeId, $page, $perPage, $search);
    }

    /**
     * Get a customer by ID with credit and payment history.
     *
     * @return array{customer: array, credits: array, payments: array}|null
     */
    public function getCustomerDetail(int $id, int $storeId): ?array
    {
        $customer = $this->customerModel->findById($id, $storeId);
        if ($customer === null) {
            return null;
        }

        $credits = $this->customerModel->getCreditHistory($id, $storeId);
        $payments = $this->customerModel->getPaymentHistory($id, $storeId);

        return [
            'customer' => $customer,
            'credits'  => $credits,
            'payments' => $payments,
        ];
    }

    /**
     * Create a new customer.
     *
     * @return array{success: bool, errors: string[], customer_id: int|null}
     */
    public function createCustomer(int $storeId, array $data): array
    {
        $errors = [];

        $name = trim($data['name'] ?? '');
        $mobile = trim($data['mobile'] ?? '');
        $email = trim($data['email'] ?? '');

        if ($name === '') {
            $errors[] = 'Customer name is required.';
        }

        if ($mobile === '') {
            $errors[] = 'Mobile number is required.';
        } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
            $errors[] = 'Mobile number must be 10 digits.';
        } elseif ($this->customerModel->mobileExistsInStore($storeId, $mobile)) {
            $errors[] = 'A customer with this mobile number already exists.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'customer_id' => null];
        }

        $customerId = $this->customerModel->create($storeId, [
            'name'   => $name,
            'mobile' => $mobile,
            'email'  => strtolower($email),
        ]);

        return ['success' => true, 'errors' => [], 'customer_id' => $customerId];
    }

    /**
     * Record a payment against a customer's outstanding balance.
     *
     * @return array{success: bool, errors: string[]}
     */
    public function recordPayment(int $storeId, int $customerId, array $data): array
    {
        $customer = $this->customerModel->findById($customerId, $storeId);
        if ($customer === null) {
            return ['success' => false, 'errors' => ['Customer not found.']];
        }

        $amount = (float) ($data['amount'] ?? 0);
        $method = trim($data['payment_method'] ?? '');
        $notes  = trim($data['notes'] ?? '');

        $errors = [];

        if ($amount <= 0) {
            $errors[] = 'Payment amount must be greater than 0.';
        }

        $outstanding = (float) $customer['outstanding_balance'];
        if ($amount > $outstanding) {
            $errors[] = 'Payment amount cannot exceed the outstanding balance of ' . number_format($outstanding, 2) . '.';
        }

        if (!in_array($method, ['cash', 'upi', 'card'], true)) {
            $errors[] = 'Invalid payment method.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $pdo = $this->customerModel->getPdo();
        $pdo->beginTransaction();

        try {
            $this->customerModel->recordPayment($storeId, $customerId, $amount, $method, $notes ?: null);
            $pdo->commit();

            return ['success' => true, 'errors' => []];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[CustomerService] recordPayment failed: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['An error occurred while recording the payment. Please try again.']];
        }
    }
}
