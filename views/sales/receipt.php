<!DOCTYPE html>
<html lang="en">
<!--
  views/sales/receipt.php — Thermal receipt page (80mm width).

  Standalone page — does NOT use app.php layout.
  Print-optimised: auto-prints on load, 80mm-width CSS.

  Expected variables:
    $sale  (array) — Sale record with items, store info:
      sale_number, sale_date, customer_name, payment_method,
      subtotal, tax_amount, total_amount, notes,
      store_name, store_address, store_mobile,
      items[] => {product_name_snapshot, sku_snapshot, quantity, unit_price, line_total}
-->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt — <?= htmlspecialchars($sale['sale_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></title>

    <style>
        /* ---- SCREEN STYLES ---- */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #f0f0f0;
            padding: 20px;
        }

        .receipt {
            width: 80mm;
            margin: 0 auto;
            background: #fff;
            padding: 10mm 5mm;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 8px;
        }

        .store-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .store-info {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        .divider {
            border: none;
            border-top: 1px dashed #999;
            margin: 6px 0;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }

        .meta-label {
            color: #666;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }

        .items-table th {
            font-size: 10px;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding: 3px 0;
            text-align: left;
        }

        .items-table th:nth-child(2),
        .items-table th:nth-child(3),
        .items-table th:nth-child(4) {
            text-align: right;
        }

        .items-table td {
            padding: 3px 0;
            font-size: 11px;
            vertical-align: top;
        }

        .items-table td:nth-child(2),
        .items-table td:nth-child(3),
        .items-table td:nth-child(4) {
            text-align: right;
        }

        .item-name {
            max-width: 30mm;
            word-wrap: break-word;
        }

        .totals-section {
            margin-top: 4px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            padding: 2px 0;
        }

        .total-row.grand {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px double #000;
            padding: 4px 0;
            margin-top: 2px;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
            color: #666;
        }

        .footer .thank-you {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin-bottom: 4px;
        }

        /* Screen-only controls */
        .screen-controls {
            width: 80mm;
            margin: 16px auto 0;
            text-align: center;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #4f46e5;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .btn-print:hover {
            background: #4338ca;
        }

        .btn-back {
            display: inline-block;
            margin-top: 8px;
            color: #6366f1;
            text-decoration: none;
            font-size: 13px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .btn-back:hover {
            text-decoration: underline;
        }

        /* ---- PRINT STYLES ---- */
        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }

            .receipt {
                box-shadow: none;
                margin: 0;
                padding: 2mm;
                width: 80mm;
            }

            .screen-controls {
                display: none !important;
            }

            /* Remove headers, footers, margins from print dialog */
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>

<?php
    $sale     = $sale ?? [];
    $items    = $sale['items'] ?? [];
    $currency = CURRENCY_SYMBOL ?? '₹';
?>

<body>

<div class="receipt">

    <!-- Store header -->
    <div class="receipt-header">
        <div class="store-name"><?= htmlspecialchars($sale['store_name'] ?? 'Store', ENT_QUOTES, 'UTF-8') ?></div>
        <?php if (!empty($sale['store_address'])): ?>
        <div class="store-info"><?= htmlspecialchars($sale['store_address'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if (!empty($sale['store_mobile'])): ?>
        <div class="store-info">Tel: <?= htmlspecialchars($sale['store_mobile'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <hr class="divider">

    <!-- Sale metadata -->
    <div class="meta-row">
        <span class="meta-label">Invoice:</span>
        <span><?= htmlspecialchars($sale['sale_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="meta-row">
        <span class="meta-label">Date:</span>
        <span><?= htmlspecialchars(date('d/m/Y h:i A', strtotime($sale['sale_date'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php if (!empty($sale['customer_name']) && $sale['customer_name'] !== 'Walk-in Customer'): ?>
    <div class="meta-row">
        <span class="meta-label">Customer:</span>
        <span><?= htmlspecialchars($sale['customer_name'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endif; ?>

    <hr class="divider">

    <!-- Line items -->
    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="item-name"><?= htmlspecialchars($item['product_name_snapshot'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= number_format((float)($item['quantity'] ?? 0), $item['quantity'] == (int)$item['quantity'] ? 0 : 1) ?></td>
                <td><?= $currency ?><?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
                <td><?= $currency ?><?= number_format((float)($item['line_total'] ?? 0), 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr class="divider">

    <!-- Totals -->
    <div class="totals-section">
        <div class="total-row">
            <span>Subtotal</span>
            <span><?= $currency ?><?= number_format((float)($sale['subtotal'] ?? 0), 2) ?></span>
        </div>
        <?php if (!empty($sale['tax_amount']) && (float)$sale['tax_amount'] > 0): ?>
        <div class="total-row">
            <span>GST</span>
            <span><?= $currency ?><?= number_format((float)$sale['tax_amount'], 2) ?></span>
        </div>
        <?php endif; ?>
        <div class="total-row grand">
            <span>TOTAL</span>
            <span><?= $currency ?><?= number_format((float)($sale['total_amount'] ?? 0), 2) ?></span>
        </div>
    </div>

    <!-- Payment method -->
    <div class="meta-row" style="margin-top: 6px;">
        <span class="meta-label">Payment:</span>
        <span style="text-transform: uppercase;"><?= htmlspecialchars($sale['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <hr class="divider">

    <!-- Footer -->
    <div class="footer">
        <div class="thank-you">Thank you for your business!</div>
        <div>Powered by Kinara Store Hub</div>
    </div>

</div>

<!-- Screen-only controls -->
<div class="screen-controls">
    <button type="button" class="btn-print" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
        Print Again
    </button>
    <br>
    <a href="/kinarahub/sales/<?= (int)($sale['id'] ?? 0) ?>" class="btn-back">Back to sale details</a>
</div>

<script>
// Auto-print on page load
window.addEventListener('DOMContentLoaded', function () {
    // Small delay to ensure CSS is rendered
    setTimeout(function () {
        window.print();
    }, 300);
});
</script>

</body>
</html>
