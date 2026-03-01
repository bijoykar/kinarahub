<?php
/**
 * views/sales/bookkeeping.php — Manual bookkeeping entry form.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $products    (array)  — Product list for search: id, sku, name, selling_price
 *   $customers   (array)  — Customer list: id, name, mobile
 *   $csrfToken   (string) — CSRF token.
 *   $errors      (array)  — Validation errors. Default: []
 */

$products  = $products ?? [];
$customers = $customers ?? [];
$csrfToken = $csrfToken ?? '';
$errors    = $errors ?? [];
?>

<!-- Page header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bookkeeping Entry</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Record a past sale manually</p>
</div>

<form method="POST" action="/kinarahub/sales" class="space-y-6 max-w-4xl" id="bookkeeping-form">
    <?= \App\Middleware\CsrfMiddleware::field() ?>
    <input type="hidden" name="entry_mode" value="booking">

    <!-- Date + Payment row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <!-- Date -->
        <div>
            <label for="bk-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Sale date</label>
            <input
                type="date"
                id="bk-date"
                name="sale_date"
                value="<?= date('Y-m-d') ?>"
                required
                max="<?= date('Y-m-d') ?>"
                class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
            >
        </div>
        <!-- Payment Method -->
        <div>
            <label for="bk-payment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Payment method</label>
            <select
                id="bk-payment"
                name="payment_method"
                required
                onchange="toggleBkCustomer()"
                class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150 appearance-none"
                style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27m6 8 4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em;"
            >
                <option value="cash">Cash</option>
                <option value="upi">UPI</option>
                <option value="card">Card</option>
                <option value="credit">Credit</option>
            </select>
        </div>
        <!-- Customer -->
        <div id="bk-customer-wrap">
            <label for="bk-customer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Customer <span id="bk-cust-opt" class="text-gray-400 font-normal">(optional)</span>
            </label>
            <select
                id="bk-customer"
                name="customer_id"
                class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150 appearance-none"
                style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27m6 8 4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em;"
            >
                <option value="">Walk-in Customer</option>
                <?php foreach ($customers as $cust): ?>
                <option value="<?= (int)$cust['id'] ?>"><?= htmlspecialchars($cust['name'] . ($cust['mobile'] ? ' (' . $cust['mobile'] . ')' : ''), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Line items -->
    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Line items</h2>
            <button type="button" onclick="addBkRow()" class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add item
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full" id="bk-items-table">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <th class="pb-2 text-left min-w-[200px]">Product</th>
                        <th class="pb-2 text-right w-24">Qty</th>
                        <th class="pb-2 text-right w-32">Unit Price (<?= CURRENCY_SYMBOL ?? '₹' ?>)</th>
                        <th class="pb-2 text-right w-32">Line Total</th>
                        <th class="pb-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="bk-items-body" class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <!-- Rows added by JS -->
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td colspan="3" class="pt-3 text-right text-sm font-bold text-gray-900 dark:text-white">Total</td>
                        <td class="pt-3 text-right text-sm font-bold text-brand-600 dark:text-brand-400" id="bk-grand-total"><?= CURRENCY_SYMBOL ?? '₹' ?>0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Notes -->
    <div class="rounded-xl bg-white dark:bg-gray-800 p-5 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <label for="bk-notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
        <textarea
            id="bk-notes"
            name="notes"
            rows="2"
            maxlength="500"
            placeholder="Any notes about this sale..."
            class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150 resize-none"
        ></textarea>
    </div>

    <!-- Submit -->
    <div class="flex justify-end">
        <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 transition-colors duration-150"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            Record sale
        </button>
    </div>
</form>

<script>
(function () {
    'use strict';

    var CURRENCY = '<?= CURRENCY_SYMBOL ?? '₹' ?>';
    var products = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    var rowCounter = 0;

    // Toggle customer required
    window.toggleBkCustomer = function () {
        var method = document.getElementById('bk-payment').value;
        var custOpt = document.getElementById('bk-cust-opt');
        var custSelect = document.getElementById('bk-customer');
        if (method === 'credit') {
            custOpt.textContent = '(required)';
            custSelect.setAttribute('required', '');
        } else {
            custOpt.textContent = '(optional)';
            custSelect.removeAttribute('required');
        }
    };

    // Add row
    window.addBkRow = function () {
        var idx = rowCounter++;
        var tbody = document.getElementById('bk-items-body');
        var tr = document.createElement('tr');
        tr.id = 'bk-row-' + idx;
        tr.className = 'align-top';

        // Build product options
        var opts = '<option value="">Search product...</option>';
        products.forEach(function (p) {
            opts += '<option value="' + p.id + '" data-price="' + p.selling_price + '">' + esc(p.sku + ' - ' + p.name) + '</option>';
        });

        tr.innerHTML =
            '<td class="py-2 pr-2">' +
            '  <select name="items[' + idx + '][product_id]" required onchange="bkProductSelected(this,' + idx + ')" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 focus:outline-none appearance-none">' + opts + '</select>' +
            '</td>' +
            '<td class="py-2 px-2">' +
            '  <input type="number" name="items[' + idx + '][quantity]" value="1" min="0.001" step="0.001" required onchange="bkCalcRow(' + idx + ')" oninput="bkCalcRow(' + idx + ')" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-right text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 focus:outline-none">' +
            '</td>' +
            '<td class="py-2 px-2">' +
            '  <input type="number" name="items[' + idx + '][unit_price]" id="bk-price-' + idx + '" value="" min="0" step="0.01" required onchange="bkCalcRow(' + idx + ')" oninput="bkCalcRow(' + idx + ')" placeholder="0.00" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-right text-gray-900 dark:text-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 focus:outline-none">' +
            '</td>' +
            '<td class="py-2 px-2 text-right text-sm font-medium text-gray-700 dark:text-gray-300 align-middle" id="bk-line-' + idx + '">' + CURRENCY + '0.00</td>' +
            '<td class="py-2 pl-2 align-middle">' +
            '  <button type="button" onclick="removeBkRow(' + idx + ')" class="rounded-lg p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" aria-label="Remove row">' +
            '    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>' +
            '  </button>' +
            '</td>';

        tbody.appendChild(tr);
    };

    window.bkProductSelected = function (sel, idx) {
        var opt = sel.options[sel.selectedIndex];
        var price = opt.dataset.price || '';
        document.getElementById('bk-price-' + idx).value = price;
        bkCalcRow(idx);
    };

    window.bkCalcRow = function (idx) {
        var row = document.getElementById('bk-row-' + idx);
        if (!row) return;
        var qty = parseFloat(row.querySelector('[name$="[quantity]"]').value) || 0;
        var price = parseFloat(row.querySelector('[name$="[unit_price]"]').value) || 0;
        var lineTotal = qty * price;
        document.getElementById('bk-line-' + idx).textContent = CURRENCY + lineTotal.toFixed(2);
        bkCalcTotal();
    };

    window.removeBkRow = function (idx) {
        var row = document.getElementById('bk-row-' + idx);
        if (row) row.remove();
        bkCalcTotal();
    };

    function bkCalcTotal() {
        var total = 0;
        document.querySelectorAll('[id^="bk-line-"]').forEach(function (el) {
            total += parseFloat(el.textContent.replace(/[^0-9.-]/g, '')) || 0;
        });
        document.getElementById('bk-grand-total').textContent = CURRENCY + total.toFixed(2);
    }

    function esc(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str)));
        return d.innerHTML;
    }

    // Start with one row
    addBkRow();
})();
</script>
