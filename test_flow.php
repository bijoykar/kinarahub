<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pdo = App\Core\Database::getInstance();
$pass = 0; $fail = 0;

function ok(string $label, bool $result, string $detail = ''): void {
    global $pass, $fail;
    $result ? $pass++ : $fail++;
    $icon = $result ? "\033[32m[PASS]\033[0m" : "\033[31m[FAIL]\033[0m";
    echo "{$icon} {$label}" . ($detail ? "  ({$detail})" : '') . PHP_EOL;
}
function section(string $title): void {
    echo PHP_EOL . "── {$title} " . str_repeat('─', max(0, 42 - strlen($title))) . PHP_EOL;
}

echo PHP_EOL . str_repeat('═', 48) . PHP_EOL;
echo "  Kinara Hub — Login & Dashboard Test Suite" . PHP_EOL;
echo str_repeat('═', 48) . PHP_EOL;

// ─── 1. DATABASE ──────────────────────────────────────────────────────────────
section('1. Database');
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
ok('Connected — 16 tables',   count($tables) === 16, count($tables) . ' tables');
foreach (['stores','staff','roles','products','sales','sale_items','customers','categories'] as $t)
    ok("  table: {$t}", in_array($t, $tables));

// ─── 2. LOGIN ─────────────────────────────────────────────────────────────────
section('2. Login');

// Store owner
$stmt = $pdo->prepare(
    'SELECT s.*, st.status AS store_status, r.name AS role_name, r.is_owner
     FROM staff s
     JOIN stores st ON st.id = s.store_id
     JOIN roles  r  ON r.id  = s.role_id
     WHERE s.email = ? LIMIT 1'
);
$stmt->execute(['owner@demo.com']);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

ok('Staff found',          !empty($staff),  $staff['name'] ?? 'not found');
ok('Password correct',     !empty($staff) && password_verify('Test@1234', $staff['password_hash']));
ok('Store active',         ($staff['store_status'] ?? '') === 'active');
ok('Staff active',         ($staff['status']       ?? '') === 'active');
ok('Role = Owner',         !empty($staff['is_owner']));
ok('Store ID = 1',         (int)($staff['store_id'] ?? 0) === 1);

// Platform admin
$adminStmt = $pdo->prepare('SELECT * FROM admins WHERE email=? LIMIT 1');
$adminStmt->execute(['admin@kinarahub.com']);
$admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
ok('Admin found',          !empty($admin));
ok('Admin password',       !empty($admin) && password_verify('Admin@123', $admin['password_hash']));

// ─── 3. DASHBOARD SERVICE ─────────────────────────────────────────────────────
section('3. Dashboard — getAllStats()');

$_SESSION['store_id'] = 1;
$svc = new App\Services\DashboardService();

try {
    $stats = $svc->getAllStats(1);
    ok('getAllStats() returned array', is_array($stats));
} catch (Throwable $e) {
    ok('getAllStats() returned array', false, $e->getMessage());
    echo "  ERROR: " . $e->getMessage() . PHP_EOL;
    goto result;
}

// Revenue KPIs
ok('today_revenue key',    array_key_exists('today_revenue',  $stats));
ok('percent_change key',   array_key_exists('percent_change', $stats));
ok('week_revenue > 0',     ($stats['week_revenue']  ?? 0) > 0,  '₹' . number_format($stats['week_revenue']  ?? 0, 2));
ok('month_revenue > 0',    ($stats['month_revenue'] ?? 0) > 0,  '₹' . number_format($stats['month_revenue'] ?? 0, 2));
echo "       today=₹"    . number_format($stats['today_revenue'] ?? 0, 2)
   . "  week=₹"         . number_format($stats['week_revenue']  ?? 0, 2)
   . "  month=₹"        . number_format($stats['month_revenue'] ?? 0, 2) . PHP_EOL;

// Stock KPIs
ok('stock_value > 0',      ($stats['stock_value'] ?? 0) > 0, '₹' . number_format($stats['stock_value'] ?? 0, 2));
ok('out_of_stock = 1',     ($stats['out_of_stock'] ?? -1) === 1,  "count=" . ($stats['out_of_stock'] ?? '?'));
ok('low_stock = 2',        ($stats['low_stock']    ?? -1) === 2,  "count=" . ($stats['low_stock']    ?? '?'));

// Collections
ok('top_products is array', is_array($stats['top_products'] ?? null));
ok('recent_sales = 10',    count($stats['recent_sales'] ?? []) === 10,
   count($stats['recent_sales'] ?? []) . ' returned');
ok('recent_sales[0] has sale_number', !empty($stats['recent_sales'][0]['sale_number'] ?? ''),
   $stats['recent_sales'][0]['sale_number'] ?? 'missing');

if (!empty($stats['top_products']))
    echo "       top today: " . implode(', ', array_map(
        fn($p) => ($p['name'] ?? $p['product_name'] ?? '?') . ' x' . ($p['qty_sold'] ?? '?'),
        $stats['top_products']
    )) . PHP_EOL;

// Sales trend
$trend = $stats['sales_trend'] ?? [];
ok('sales_trend has labels',     !empty($trend['labels']  ?? []));
ok('sales_trend has amounts',    !empty($trend['amounts'] ?? []));
ok('labels = amounts count',     count($trend['labels'] ?? []) === count($trend['amounts'] ?? []));
echo "       week: [" . implode(', ', array_map(
    fn($v) => '₹' . number_format($v, 0), $trend['amounts'] ?? []
)) . "]" . PHP_EOL;

// Payment breakdown — returns {labels: ['Cash',...], amounts: [...]}
$pay = $stats['payment_breakdown'] ?? [];
ok('payment_breakdown has labels',  isset($pay['labels'])  && is_array($pay['labels']));
ok('payment_breakdown has amounts', isset($pay['amounts']) && is_array($pay['amounts']));
ok('payment_breakdown counts match', count($pay['labels'] ?? []) === count($pay['amounts'] ?? []));
$payMethods = array_map('strtolower', $pay['labels'] ?? []);
echo '       payment methods: ' . implode(', ', $payMethods ?: ['(none this month)']) . PHP_EOL;

// Stock distribution — returns {labels: ['In Stock','Low Stock','Out of Stock'], counts: [n,n,n]}
$dist = $stats['stock_distribution'] ?? [];
ok('stock_distribution has labels', isset($dist['labels'])  && is_array($dist['labels']));
ok('stock_distribution has counts', isset($dist['counts'])  && is_array($dist['counts']));
ok('stock_distribution sum = 20',  array_sum($dist['counts'] ?? []) === 20,
   'sum=' . array_sum($dist['counts'] ?? []));
echo '       in=' . ($dist['counts'][0] ?? '?') . '  low=' . ($dist['counts'][1] ?? '?') . '  out=' . ($dist['counts'][2] ?? '?') . PHP_EOL;

// Chart data endpoints
section('4. Chart Data (AJAX)');
try {
    $trendWeek  = $svc->salesTrend(1, 'week');
    $trendMonth = $svc->salesTrend(1, 'month');
    $trendYear  = $svc->salesTrend(1, 'year');
    ok('salesTrend(week)',  count($trendWeek['labels'] ?? [])  === 7,           count($trendWeek['labels']  ?? []) . ' pts');
    ok('salesTrend(month)', count($trendMonth['labels'] ?? []) === (int)date('t'), count($trendMonth['labels'] ?? []) . ' pts');
    ok('salesTrend(year)',  count($trendYear['labels']  ?? []) === (int)date('n'), count($trendYear['labels']  ?? []) . ' pts');

    $payBreak = $svc->paymentMethodBreakdown(1, 'month');
    ok('paymentBreakdown JSON-serialisable', json_encode($payBreak) !== false);

    $stockDist = $svc->stockStatusDistribution(1);
    ok('stockDistribution sum = 20', array_sum($stockDist['counts'] ?? []) === 20,
       'in=' . ($stockDist['counts'][0] ?? '?') . ' low=' . ($stockDist['counts'][1] ?? '?') . ' out=' . ($stockDist['counts'][2] ?? '?'));
} catch (Throwable $e) {
    ok('Chart data methods', false, $e->getMessage());
}

// ─── 5. DATA INTEGRITY ────────────────────────────────────────────────────────
section('5. Data Integrity');

$c = fn(string $q) => (int)$pdo->query($q)->fetchColumn();

ok('23 total sales',          $c('SELECT COUNT(*)        FROM sales     WHERE store_id=1') === 23,
   $c('SELECT COUNT(*) FROM sales WHERE store_id=1') . ' found');
$totalRev = (float)$pdo->query('SELECT SUM(total_amount) FROM sales WHERE store_id=1')->fetchColumn();
ok('Revenue > ₹60,000',       $totalRev > 60000, '₹' . number_format($totalRev, 2));
ok('20 products',             $c('SELECT COUNT(*)        FROM products   WHERE store_id=1') === 20);
ok('Sale items exist',        $c('SELECT COUNT(*)        FROM sale_items WHERE store_id=1') > 0,
   $c('SELECT COUNT(*) FROM sale_items WHERE store_id=1') . ' rows');
ok('5 named customers',       $c('SELECT COUNT(*) FROM customers WHERE store_id=1 AND is_default=0') === 5);
ok('2 credit customers',      $c('SELECT COUNT(*) FROM customers WHERE store_id=1 AND outstanding_balance>0') === 2);
ok('5 categories',            $c('SELECT COUNT(*) FROM categories WHERE store_id=1') === 5);

echo PHP_EOL . "  Credit dues:" . PHP_EOL;
foreach ($pdo->query('SELECT name,outstanding_balance FROM customers WHERE store_id=1 AND outstanding_balance>0')->fetchAll() as $r)
    echo "    {$r['name']}: ₹" . number_format((float)$r['outstanding_balance'], 2) . PHP_EOL;

// ─── RESULT ───────────────────────────────────────────────────────────────────
result:
$total = $pass + $fail;
echo PHP_EOL . str_repeat('═', 48) . PHP_EOL;
if ($fail === 0) {
    echo "  \033[32mAll {$total} tests passed\033[0m" . PHP_EOL;
} else {
    echo "  \033[31m{$fail} FAILED\033[0m / {$total} total  ({$pass} passed)" . PHP_EOL;
}
echo str_repeat('═', 48) . PHP_EOL . PHP_EOL;

exit($fail > 0 ? 1 : 0);
