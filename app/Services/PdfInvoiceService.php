<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\TenantScope;
use PDO;

/**
 * PdfInvoiceService — Generates branded PDF invoices for sales.
 *
 * Uses mPDF when available (via Composer). Falls back to an HTML-based
 * printable page when mPDF is not installed.
 */
class PdfInvoiceService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Generate and stream a PDF invoice for the given sale.
     *
     * Sends Content-Disposition: attachment headers and exits.
     * If mPDF is not available, outputs a printable HTML page instead.
     */
    public function generateInvoicePdf(int $saleId, int $storeId): void
    {
        $sale  = $this->fetchSale($saleId, $storeId);
        $store = $this->fetchStore($storeId);

        if ($sale === null) {
            http_response_code(404);
            echo 'Sale not found.';
            exit;
        }

        $html = $this->buildInvoiceHtml($sale, $store);

        if (class_exists('\\Mpdf\\Mpdf')) {
            $this->streamMpdf($html, $sale['sale_number'] ?? 'invoice');
        } else {
            $this->streamHtmlFallback($html);
        }
    }

    // -----------------------------------------------------------------------
    // Data fetchers
    // -----------------------------------------------------------------------

    private function fetchSale(int $saleId, int $storeId): ?array
    {
        $sql = 'SELECT s.*, c.name AS customer_name
                FROM sales s
                LEFT JOIN customers c ON c.id = s.customer_id
                WHERE s.id = ?';
        $params = [$saleId];
        $sql = TenantScope::appendWhere($sql, 's');
        TenantScope::apply($params, $storeId);
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sale === false) {
            return null;
        }

        // Fetch sale items.
        $itemsSql = 'SELECT * FROM sale_items WHERE sale_id = ?';
        $itemsParams = [$saleId];
        $itemsSql = TenantScope::appendWhere($itemsSql);
        TenantScope::apply($itemsParams, $storeId);
        $itemsSql .= ' ORDER BY id ASC';

        $itemsStmt = $this->pdo->prepare($itemsSql);
        $itemsStmt->execute($itemsParams);
        $sale['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        return $sale;
    }

    private function fetchStore(int $storeId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stores WHERE id = ? LIMIT 1');
        $stmt->execute([$storeId]);
        $store = $stmt->fetch(PDO::FETCH_ASSOC);

        return $store !== false ? $store : [];
    }

    // -----------------------------------------------------------------------
    // HTML builder
    // -----------------------------------------------------------------------

    private function buildInvoiceHtml(array $sale, array $store): string
    {
        $currency = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₹';
        $items    = $sale['items'] ?? [];

        $storeName    = htmlspecialchars($store['name'] ?? 'Store', ENT_QUOTES, 'UTF-8');
        $storeAddress = $this->formatStoreAddress($store);
        $storeMobile  = htmlspecialchars($store['mobile'] ?? '', ENT_QUOTES, 'UTF-8');

        $saleNumber   = htmlspecialchars($sale['sale_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $saleDate     = htmlspecialchars(date('d M Y', strtotime($sale['sale_date'] ?? 'now')), ENT_QUOTES, 'UTF-8');
        $customerName = htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer', ENT_QUOTES, 'UTF-8');
        $paymentMethod = htmlspecialchars(ucfirst($sale['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8');

        // Logo
        $logoHtml = '';
        if (!empty($store['logo_path'])) {
            $logoFile = dirname(__DIR__, 2) . '/' . ltrim($store['logo_path'], '/');
            if (file_exists($logoFile)) {
                $logoHtml = '<img src="' . htmlspecialchars($logoFile, ENT_QUOTES, 'UTF-8') . '" style="max-height:60px;max-width:180px;margin-bottom:8px;" alt="Logo">';
            }
        }

        // Build items rows
        $rowsHtml = '';
        foreach ($items as $i => $item) {
            $productName = htmlspecialchars($item['product_name_snapshot'] ?? '', ENT_QUOTES, 'UTF-8');
            $sku         = htmlspecialchars($item['sku_snapshot'] ?? '', ENT_QUOTES, 'UTF-8');
            $qty         = number_format((float)($item['quantity'] ?? 0), 2);
            $unitPrice   = $currency . number_format((float)($item['unit_price'] ?? 0), 2);
            $lineTotal   = $currency . number_format((float)($item['line_total'] ?? 0), 2);

            $rowsHtml .= "<tr>
                <td style=\"padding:6px 8px;border-bottom:1px solid #eee;text-align:center;\">" . ($i + 1) . "</td>
                <td style=\"padding:6px 8px;border-bottom:1px solid #eee;\">{$productName}</td>
                <td style=\"padding:6px 8px;border-bottom:1px solid #eee;text-align:center;\">{$sku}</td>
                <td style=\"padding:6px 8px;border-bottom:1px solid #eee;text-align:right;\">{$qty}</td>
                <td style=\"padding:6px 8px;border-bottom:1px solid #eee;text-align:right;\">{$unitPrice}</td>
                <td style=\"padding:6px 8px;border-bottom:1px solid #eee;text-align:right;\">{$lineTotal}</td>
            </tr>";
        }

        $subtotal   = $currency . number_format((float)($sale['subtotal'] ?? 0), 2);
        $taxAmount  = $currency . number_format((float)($sale['tax_amount'] ?? 0), 2);
        $totalAmount = $currency . number_format((float)($sale['total_amount'] ?? 0), 2);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
    .invoice-box { max-width: 800px; margin: auto; padding: 20px; }
    .header { text-align: center; margin-bottom: 20px; }
    .store-name { font-size: 22px; font-weight: bold; color: #1a1a1a; margin-bottom: 4px; }
    .store-info { font-size: 11px; color: #666; }
    .meta-table { width: 100%; margin-bottom: 16px; }
    .meta-table td { padding: 3px 0; font-size: 12px; }
    .meta-label { color: #888; width: 120px; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .items-table th { background: #4f46e5; color: #fff; padding: 8px; font-size: 11px; text-transform: uppercase; }
    .items-table th:first-child { border-radius: 4px 0 0 0; }
    .items-table th:last-child { border-radius: 0 4px 0 0; }
    .totals-table { width: 300px; margin-left: auto; }
    .totals-table td { padding: 4px 8px; font-size: 12px; }
    .totals-table .grand td { font-size: 14px; font-weight: bold; border-top: 2px solid #333; padding-top: 8px; }
    .footer { text-align: center; margin-top: 30px; padding-top: 16px; border-top: 1px solid #eee; font-size: 11px; color: #888; }
    @media print {
        body { padding: 0; }
        .invoice-box { padding: 10px; }
    }
</style>
</head>
<body>
<div class="invoice-box">

    <div class="header">
        {$logoHtml}
        <div class="store-name">{$storeName}</div>
        <div class="store-info">{$storeAddress}</div>
        {$storeMobile}
    </div>

    <hr style="border:none;border-top:2px solid #4f46e5;margin:12px 0 16px;">

    <table class="meta-table">
        <tr><td class="meta-label">Invoice Number:</td><td><strong>{$saleNumber}</strong></td>
            <td class="meta-label" style="text-align:right;">Date:</td><td style="text-align:right;">{$saleDate}</td></tr>
        <tr><td class="meta-label">Customer:</td><td>{$customerName}</td>
            <td class="meta-label" style="text-align:right;">Payment:</td><td style="text-align:right;">{$paymentMethod}</td></tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="text-align:center;width:40px;">#</th>
                <th style="text-align:left;">Product</th>
                <th style="text-align:center;">SKU</th>
                <th style="text-align:right;">Qty</th>
                <th style="text-align:right;">Unit Price</th>
                <th style="text-align:right;">Line Total</th>
            </tr>
        </thead>
        <tbody>
            {$rowsHtml}
        </tbody>
    </table>

    <table class="totals-table">
        <tr><td style="text-align:right;">Subtotal:</td><td style="text-align:right;">{$subtotal}</td></tr>
        <tr><td style="text-align:right;">GST (18%):</td><td style="text-align:right;">{$taxAmount}</td></tr>
        <tr class="grand"><td style="text-align:right;">Grand Total:</td><td style="text-align:right;">{$totalAmount}</td></tr>
    </table>

    <div class="footer">
        <strong>Thank you for your business!</strong><br>
        Powered by Kinara Store Hub
    </div>

</div>
</body>
</html>
HTML;
    }

    // -----------------------------------------------------------------------
    // Output methods
    // -----------------------------------------------------------------------

    private function streamMpdf(string $html, string $saleNumber): void
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->WriteHTML($html);

        $filename = 'Invoice-' . preg_replace('/[^A-Za-z0-9\-]/', '', $saleNumber) . '.pdf';

        $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    private function streamHtmlFallback(string $html): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function formatStoreAddress(array $store): string
    {
        $parts = array_filter([
            $store['address_street'] ?? '',
            $store['address_city'] ?? '',
            $store['address_state'] ?? '',
            $store['address_pincode'] ?? '',
        ]);

        return htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
    }
}
