<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductModel;
use App\Models\SaleModel;

/**
 * SaleService — Business logic for sales management.
 *
 * CRITICAL: Sale creation is an atomic DB transaction:
 *  1. Insert sale record (generate INV-XXXXX sale_number per store).
 *  2. Insert all sale_items with product name/SKU/cost snapshots.
 *  3. Decrement stock for each product (check version, 409 on mismatch, rollback on any error).
 *  4. If payment_method=credit, create customer_credit record.
 */
class SaleService
{
    private SaleModel $saleModel;
    private ProductModel $productModel;

    public function __construct()
    {
        $this->saleModel = new SaleModel();
        $this->productModel = new ProductModel();
    }

    /**
     * List sales with pagination and filters.
     *
     * @return array{sales: array, total: int}
     */
    public function listSales(int $storeId, int $page, int $perPage, string $search = '', string $from = '', string $to = '', string $paymentMethod = ''): array
    {
        return $this->saleModel->listPaginated($storeId, $page, $perPage, $search, $from, $to, $paymentMethod);
    }

    /**
     * Get a single sale with its items.
     *
     * @return array<string, mixed>|null
     */
    public function getSale(int $id, int $storeId): ?array
    {
        return $this->saleModel->findById($id, $storeId);
    }

    /**
     * Create a new sale.
     *
     * This is the most critical operation in the system: it must be fully atomic.
     *
     * @param int    $storeId
     * @param int    $staffId  The staff member creating the sale.
     * @param array  $saleData  { entry_mode, sale_date, customer_id, payment_method, notes }
     * @param array  $items     [ { product_id, variant_id, quantity, unit_price } ]
     * @return array{success: bool, errors: string[], sale_id: int|null, conflict: bool}
     */
    public function createSale(int $storeId, int $staffId, array $saleData, array $items): array
    {
        // Validate basic requirements.
        $errors = $this->validateSale($saleData, $items);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'sale_id' => null, 'conflict' => false];
        }

        // Credit sales require a named customer (not the default Walk-in Customer).
        if ($saleData['payment_method'] === 'credit') {
            if (empty($saleData['customer_id'])) {
                return ['success' => false, 'errors' => ['Credit sales require a named customer.'], 'sale_id' => null, 'conflict' => false];
            }

            // Check if customer_id is the default Walk-in Customer.
            $defaultCustomerId = $this->saleModel->getDefaultCustomerId($storeId);
            if ((int) $saleData['customer_id'] === $defaultCustomerId) {
                return ['success' => false, 'errors' => ['Credit sales cannot be assigned to Walk-in Customer. Please select a named customer.'], 'sale_id' => null, 'conflict' => false];
            }
        }

        $pdo = $this->saleModel->getPdo();
        $pdo->beginTransaction();

        try {
            // 1. Generate sale number.
            $saleNumber = $this->saleModel->generateSaleNumber($storeId);

            // 2. Build sale items with snapshots and calculate totals.
            $processedItems = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $product = $this->productModel->findById((int) $item['product_id'], $storeId);

                if ($product === null) {
                    $pdo->rollBack();
                    return ['success' => false, 'errors' => ['Product not found: ID ' . $item['product_id']], 'sale_id' => null, 'conflict' => false];
                }

                $quantity = (float) $item['quantity'];
                $unitPrice = (float) ($item['unit_price'] ?? $product['selling_price']);
                $lineTotal = round($quantity * $unitPrice, 2);

                $processedItems[] = [
                    'product_id'            => (int) $item['product_id'],
                    'variant_id'            => (int) ($item['variant_id'] ?? 0),
                    'product_name_snapshot' => $product['name'],
                    'sku_snapshot'          => $product['sku'],
                    'quantity'              => $quantity,
                    'unit_price'            => $unitPrice,
                    'cost_price_snapshot'   => (float) $product['cost_price'],
                    'line_total'            => $lineTotal,
                    'product_version'       => (int) $product['version'],
                ];

                $subtotal += $lineTotal;
            }

            $taxAmount = 0.0; // Tax calculation can be added in future phase.
            $totalAmount = round($subtotal + $taxAmount, 2);

            // 3. Determine customer_id (default to Walk-in if not provided and not credit).
            $customerId = $saleData['customer_id'] ?? null;
            if (empty($customerId) && $saleData['payment_method'] !== 'credit') {
                $customerId = $this->saleModel->getDefaultCustomerId($storeId);
            }

            // 4. Insert sale record.
            $saleId = $this->saleModel->createSale($storeId, [
                'sale_number'    => $saleNumber,
                'sale_date'      => $saleData['sale_date'] ?? date('Y-m-d'),
                'entry_mode'     => $saleData['entry_mode'] ?? 'pos',
                'customer_id'    => $customerId,
                'payment_method' => $saleData['payment_method'],
                'subtotal'       => $subtotal,
                'tax_amount'     => $taxAmount,
                'total_amount'   => $totalAmount,
                'notes'          => $saleData['notes'] ?? null,
                'created_by'     => $staffId,
            ]);

            // 5. Insert sale items and decrement stock (with optimistic locking).
            foreach ($processedItems as $processedItem) {
                // Insert the sale item.
                $this->saleModel->createSaleItem($saleId, $storeId, $processedItem);

                // Decrement stock with optimistic locking.
                // Only for POS mode (bookkeeping may record past sales).
                if (($saleData['entry_mode'] ?? 'pos') === 'pos') {
                    $decremented = $this->productModel->decrementStock(
                        $processedItem['product_id'],
                        $storeId,
                        $processedItem['quantity'],
                        $processedItem['product_version']
                    );

                    if (!$decremented) {
                        $pdo->rollBack();
                        return [
                            'success'  => false,
                            'errors'   => ["Stock conflict for product '{$processedItem['product_name_snapshot']}' (SKU: {$processedItem['sku_snapshot']}). The product was modified by another user. Please try again."],
                            'sale_id'  => null,
                            'conflict' => true,
                        ];
                    }
                }
            }

            // 6. If payment_method=credit, create customer_credit record.
            if ($saleData['payment_method'] === 'credit' && !empty($customerId)) {
                $this->saleModel->createCreditRecord($storeId, (int) $customerId, $saleId, $totalAmount);
            }

            $pdo->commit();

            return ['success' => true, 'errors' => [], 'sale_id' => $saleId, 'conflict' => false];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[SaleService] createSale failed: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['An error occurred while processing the sale. Please try again.'], 'sale_id' => null, 'conflict' => false];
        }
    }

    /**
     * Get all active products for a store (for POS product grid).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProductsForPos(int $storeId): array
    {
        $result = $this->productModel->listPaginated($storeId, 1, 10000, '', 0, 'active');

        return $result['products'];
    }

    /**
     * Get all customers for a store (for customer dropdown).
     *
     * @return array<int, array{id: int, name: string, mobile: string|null}>
     */
    public function getCustomers(int $storeId): array
    {
        return $this->saleModel->listCustomers($storeId);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function validateSale(array $saleData, array $items): array
    {
        $errors = [];

        if (empty($items)) {
            $errors[] = 'At least one item is required.';
        }

        if (empty($saleData['payment_method'])) {
            $errors[] = 'Payment method is required.';
        } elseif (!in_array($saleData['payment_method'], ['cash', 'upi', 'card', 'credit'], true)) {
            $errors[] = 'Invalid payment method.';
        }

        foreach ($items as $index => $item) {
            if (empty($item['product_id'])) {
                $errors[] = "Item " . ($index + 1) . ": product is required.";
            }
            if (empty($item['quantity']) || (float) $item['quantity'] <= 0) {
                $errors[] = "Item " . ($index + 1) . ": quantity must be greater than 0.";
            }
        }

        return $errors;
    }
}
