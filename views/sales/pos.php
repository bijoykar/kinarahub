<?php
/**
 * views/sales/pos.php — POS terminal view.
 *
 * Rendered inside views/layouts/app.php.
 *
 * Expected variables:
 *   $products    (array)  — All active products (preloaded for client-side search): id, sku, name, selling_price, stock_quantity, stock_status, category_name, uom_abbreviation, variants[]
 *   $customers   (array)  — Customer list for credit sales: id, name, mobile
 *   $csrfToken   (string) — CSRF token.
 */

$products  = $products ?? [];
$customers = $customers ?? [];
$csrfToken = $csrfToken ?? '';
?>

<div class="flex flex-col lg:flex-row gap-4 h-[calc(100vh-8rem)] min-h-[500px]">

    <!-- ================================================================
         LEFT PANEL: Product Search & Grid
         ================================================================ -->
    <div class="flex flex-col flex-1 min-w-0 lg:max-w-[55%]">
        <!-- Search -->
        <div class="relative mb-3">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400 dark:text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </span>
            <input
                type="search"
                id="pos-search"
                placeholder="Search products by name or SKU... (Ctrl+K)"
                autofocus
                class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 pl-11 pr-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150"
            >
        </div>

        <!-- Product grid -->
        <div id="pos-products" class="flex-1 overflow-y-auto rounded-xl bg-white dark:bg-gray-800 ring-1 ring-gray-200 dark:ring-gray-700 p-3">
            <div id="pos-product-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                <!-- Populated by JS -->
            </div>
            <div id="pos-no-results" class="hidden flex items-center justify-center h-32 text-sm text-gray-400 dark:text-gray-500">
                No products found
            </div>
        </div>
    </div>

    <!-- ================================================================
         RIGHT PANEL: Cart
         ================================================================ -->
    <div class="flex flex-col w-full lg:w-[45%] lg:min-w-[380px] rounded-xl bg-white dark:bg-gray-800 ring-1 ring-gray-200 dark:ring-gray-700 shadow-sm">
        <!-- Cart header -->
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-4 py-3">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Cart</h2>
            <button
                type="button"
                id="pos-clear-cart"
                class="text-xs font-medium text-red-500 hover:text-red-600 dark:hover:text-red-400 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                disabled
                aria-label="Clear all cart items"
            >
                Clear all
            </button>
        </div>

        <!-- Cart items -->
        <div id="pos-cart-items" class="flex-1 overflow-y-auto px-4 py-3 space-y-2 min-h-[150px]">
            <div id="pos-cart-empty" class="flex flex-col items-center justify-center h-full text-center py-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300 dark:text-gray-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                </svg>
                <p class="text-sm text-gray-400 dark:text-gray-500">Search and add products to start a sale</p>
            </div>
        </div>

        <!-- Totals + Payment + Submit -->
        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 space-y-3 flex-shrink-0">
            <!-- Totals -->
            <div class="space-y-1">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                    <span id="pos-subtotal" class="font-medium text-gray-700 dark:text-gray-300"><?= CURRENCY_SYMBOL ?? '₹' ?>0.00</span>
                </div>
                <div class="flex items-center justify-between text-base font-bold">
                    <span class="text-gray-900 dark:text-white">Total</span>
                    <span id="pos-total" class="text-brand-600 dark:text-brand-400"><?= CURRENCY_SYMBOL ?? '₹' ?>0.00</span>
                </div>
            </div>

            <!-- Payment method -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Payment method</label>
                <div class="grid grid-cols-4 gap-1.5" id="pos-payment-methods">
                    <?php foreach (['cash' => 'Cash', 'upi' => 'UPI', 'card' => 'Card', 'credit' => 'Credit'] as $val => $lbl): ?>
                    <button
                        type="button"
                        data-method="<?= $val ?>"
                        onclick="selectPaymentMethod('<?= $val ?>')"
                        class="pos-pay-btn rounded-xl py-2 text-xs font-semibold text-center transition-colors duration-150 ring-1 ring-inset <?= $val === 'cash' ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' ?>"
                    >
                        <?= $lbl ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="pos-payment-method" value="cash">
            </div>

            <!-- Customer (for credit) -->
            <div id="pos-customer-section" class="hidden">
                <label for="pos-customer" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Customer (required for credit)</label>
                <select
                    id="pos-customer"
                    class="block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700/50 px-4 py-2.5 text-sm text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition-colors duration-150 appearance-none"
                    style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27m6 8 4 4 4-4%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em;"
                >
                    <option value="">Select customer...</option>
                    <?php foreach ($customers as $cust): ?>
                    <option value="<?= (int)$cust['id'] ?>"><?= htmlspecialchars($cust['name'] . ($cust['mobile'] ? ' (' . $cust['mobile'] . ')' : ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit -->
            <form id="pos-form" method="POST" action="/kinarahub/sales">
                <?= \App\Middleware\CsrfMiddleware::field() ?>
                <input type="hidden" name="entry_mode" value="pos">
                <input type="hidden" id="pos-cart-data" name="cart_data" value="[]">
                <input type="hidden" id="pos-payment-input" name="payment_method" value="cash">
                <input type="hidden" id="pos-customer-input" name="customer_id" value="">
                <button
                    type="submit"
                    id="pos-submit"
                    disabled
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-green-600 px-4 py-3 text-sm font-bold text-white shadow-sm shadow-green-600/30 hover:bg-green-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    Complete Sale
                </button>
            </form>
        </div>
    </div>

</div>

<script>
(function () {
    'use strict';

    var CURRENCY = '<?= CURRENCY_SYMBOL ?? '₹' ?>';
    var allProducts = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    var cart = [];

    // Status badges
    var statusClasses = {
        in_stock: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        low_stock: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
        out_of_stock: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
    };
    var statusLabels = { in_stock: 'In Stock', low_stock: 'Low', out_of_stock: 'Out' };

    // Render product grid
    function renderProducts(filter) {
        var grid = document.getElementById('pos-product-grid');
        var noResults = document.getElementById('pos-no-results');
        var filtered = allProducts;

        if (filter) {
            var q = filter.toLowerCase();
            filtered = allProducts.filter(function (p) {
                return p.name.toLowerCase().indexOf(q) !== -1 || p.sku.toLowerCase().indexOf(q) !== -1;
            });
        }

        if (!filtered.length) {
            grid.innerHTML = '';
            noResults.classList.remove('hidden');
            return;
        }
        noResults.classList.add('hidden');

        grid.innerHTML = filtered.map(function (p) {
            var isOut = p.stock_status === 'out_of_stock';
            var badgeClass = statusClasses[p.stock_status] || statusClasses.in_stock;
            return '<button type="button" ' + (isOut ? 'disabled' : '') + ' onclick="addToCart(' + p.id + ')" class="flex flex-col items-start p-3 rounded-xl text-left transition-all duration-150 ring-1 ring-gray-200 dark:ring-gray-700 ' + (isOut ? 'opacity-50 cursor-not-allowed bg-gray-50 dark:bg-gray-800' : 'bg-white dark:bg-gray-800 hover:ring-brand-400 dark:hover:ring-brand-500 hover:shadow-md cursor-pointer') + '">' +
                '<span class="text-xs font-mono text-gray-400 dark:text-gray-500 mb-1">' + esc(p.sku) + '</span>' +
                '<span class="text-sm font-medium text-gray-900 dark:text-white truncate w-full leading-tight">' + esc(p.name) + '</span>' +
                '<div class="flex items-center justify-between w-full mt-2 gap-2">' +
                '<span class="text-sm font-bold text-brand-600 dark:text-brand-400">' + CURRENCY + Number(p.selling_price).toFixed(2) + '</span>' +
                '<span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ' + badgeClass + '">' + (statusLabels[p.stock_status] || 'OK') + '</span>' +
                '</div></button>';
        }).join('');
    }

    // Search handler
    var searchInput = document.getElementById('pos-search');
    var searchTimer;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { renderProducts(searchInput.value); }, 150);
    });

    // Ctrl+K to focus search
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });

    // Add to cart
    window.addToCart = function (productId) {
        var product = allProducts.find(function (p) { return p.id === productId; });
        if (!product || product.stock_status === 'out_of_stock') return;

        var existing = cart.find(function (c) { return c.product_id === productId; });
        if (existing) {
            if (existing.quantity < product.stock_quantity) {
                existing.quantity++;
            } else {
                if (typeof showToast === 'function') showToast('Not enough stock', 'warning');
                return;
            }
        } else {
            cart.push({
                product_id: productId,
                name: product.name,
                sku: product.sku,
                unit_price: parseFloat(product.selling_price),
                quantity: 1,
                max_qty: parseFloat(product.stock_quantity),
            });
        }
        renderCart();
    };

    // Render cart
    function renderCart() {
        var container = document.getElementById('pos-cart-items');
        var emptyMsg = document.getElementById('pos-cart-empty');
        var clearBtn = document.getElementById('pos-clear-cart');
        var submitBtn = document.getElementById('pos-submit');

        if (!cart.length) {
            emptyMsg.classList.remove('hidden');
            container.querySelectorAll('.cart-item').forEach(function (el) { el.remove(); });
            clearBtn.disabled = true;
            submitBtn.disabled = true;
            updateTotals();
            return;
        }

        emptyMsg.classList.add('hidden');
        clearBtn.disabled = false;
        submitBtn.disabled = false;

        // Remove old items
        container.querySelectorAll('.cart-item').forEach(function (el) { el.remove(); });

        cart.forEach(function (item, idx) {
            var lineTotal = (item.quantity * item.unit_price).toFixed(2);
            var div = document.createElement('div');
            div.className = 'cart-item flex items-center gap-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 px-3 py-2.5 ring-1 ring-gray-200 dark:ring-gray-700';
            div.innerHTML =
                '<div class="flex-1 min-w-0">' +
                '  <p class="text-sm font-medium text-gray-900 dark:text-white truncate">' + esc(item.name) + '</p>' +
                '  <p class="text-xs text-gray-500 dark:text-gray-400">' + CURRENCY + item.unit_price.toFixed(2) + ' each</p>' +
                '</div>' +
                '<div class="flex items-center gap-1.5">' +
                '  <button type="button" onclick="changeQty(' + idx + ',-1)" class="flex h-7 w-7 items-center justify-center rounded-lg bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors text-sm font-bold" aria-label="Decrease quantity">-</button>' +
                '  <span class="w-8 text-center text-sm font-semibold text-gray-900 dark:text-white">' + item.quantity + '</span>' +
                '  <button type="button" onclick="changeQty(' + idx + ',1)" class="flex h-7 w-7 items-center justify-center rounded-lg bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors text-sm font-bold" aria-label="Increase quantity">+</button>' +
                '</div>' +
                '<span class="text-sm font-bold text-gray-900 dark:text-white w-20 text-right">' + CURRENCY + lineTotal + '</span>' +
                '<button type="button" onclick="removeFromCart(' + idx + ')" class="text-gray-400 hover:text-red-500 transition-colors p-1" aria-label="Remove item">' +
                '  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>' +
                '</button>';
            container.insertBefore(div, emptyMsg);
        });

        updateTotals();
    }

    window.changeQty = function (idx, delta) {
        if (!cart[idx]) return;
        var newQty = cart[idx].quantity + delta;
        if (newQty < 1) {
            removeFromCart(idx);
            return;
        }
        if (newQty > cart[idx].max_qty) {
            if (typeof showToast === 'function') showToast('Not enough stock', 'warning');
            return;
        }
        cart[idx].quantity = newQty;
        renderCart();
    };

    window.removeFromCart = function (idx) {
        cart.splice(idx, 1);
        renderCart();
    };

    // Clear cart
    document.getElementById('pos-clear-cart').addEventListener('click', function () {
        cart = [];
        renderCart();
    });

    // Totals
    function updateTotals() {
        var subtotal = cart.reduce(function (sum, item) { return sum + (item.quantity * item.unit_price); }, 0);
        document.getElementById('pos-subtotal').textContent = CURRENCY + subtotal.toFixed(2);
        document.getElementById('pos-total').textContent = CURRENCY + subtotal.toFixed(2);
    }

    // Payment method selection
    window.selectPaymentMethod = function (method) {
        document.getElementById('pos-payment-method').value = method;
        document.getElementById('pos-payment-input').value = method;
        document.querySelectorAll('.pos-pay-btn').forEach(function (btn) {
            if (btn.dataset.method === method) {
                btn.className = 'pos-pay-btn rounded-xl py-2 text-xs font-semibold text-center transition-colors duration-150 ring-1 ring-inset bg-brand-600 text-white ring-brand-600';
            } else {
                btn.className = 'pos-pay-btn rounded-xl py-2 text-xs font-semibold text-center transition-colors duration-150 ring-1 ring-inset bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600';
            }
        });
        // Show/hide customer section
        var custSection = document.getElementById('pos-customer-section');
        var custSelect = document.getElementById('pos-customer');
        if (method === 'credit') {
            custSection.classList.remove('hidden');
            custSelect.setAttribute('required', '');
        } else {
            custSection.classList.add('hidden');
            custSelect.removeAttribute('required');
            custSelect.value = '';
            document.getElementById('pos-customer-input').value = '';
        }
    };

    // Customer selection
    document.getElementById('pos-customer').addEventListener('change', function () {
        document.getElementById('pos-customer-input').value = this.value;
    });

    // Form submit
    document.getElementById('pos-form').addEventListener('submit', function (e) {
        if (!cart.length) {
            e.preventDefault();
            if (typeof showToast === 'function') showToast('Cart is empty', 'error');
            return;
        }
        if (document.getElementById('pos-payment-method').value === 'credit' && !document.getElementById('pos-customer').value) {
            e.preventDefault();
            if (typeof showToast === 'function') showToast('Please select a customer for credit sale', 'error');
            return;
        }
        document.getElementById('pos-cart-data').value = JSON.stringify(cart.map(function (item) {
            return { product_id: item.product_id, quantity: item.quantity, unit_price: item.unit_price };
        }));
    });

    // Helpers
    function esc(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str)));
        return d.innerHTML;
    }

    // Init
    renderProducts('');
})();
</script>
