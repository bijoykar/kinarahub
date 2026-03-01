<!DOCTYPE html>
<html lang="en" class="h-full">
<!--
  views/layouts/app.php — Main authenticated application shell.

  Expected variables (all optional with sensible defaults):
    $pageTitle    (string)  — <title> text. Default: 'Kinara Store Hub'
    $breadcrumb   (array)   — [['label'=>'...','url'=>'...'], ...] Default: []
    $content      (string)  — Pre-rendered HTML string to inject into <main>.
    $view         (string)  — Absolute path to a view file to include inside <main>.
    $includeCharts (bool)   — Whether to load Chart.js CDN. Default: false
    $currentPath  (string)  — Current URL path for sidebar active states.

  Session variables consumed:
    $_SESSION['store_name']   — Displayed in sidebar and header chip.
    $_SESSION['staff_name']   — Displayed in sidebar footer and header.
    $_SESSION['role_id']      — Used for permission-filtered nav (Phase 4).
    $_SESSION['store_logo']   — URL to store logo image, or empty string.
-->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle ?? 'Kinara Store Hub', ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Favicon placeholder -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%234f46e5'/><text y='22' x='6' font-size='18' fill='white' font-family='sans-serif' font-weight='bold'>K</text></svg>">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:  '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                    },
                    transitionProperty: {
                        colors: 'color, background-color, border-color, text-decoration-color, fill, stroke',
                    },
                },
            },
        };
    </script>

    <?php if (!empty($includeCharts)): ?>
    <!-- Chart.js — only loaded when $includeCharts is truthy (dashboard pages) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
    <?php endif; ?>

    <!--
      Dark-mode init: runs synchronously BEFORE the body is painted.
      Reads localStorage and applies/removes the `dark` class on <html>
      immediately to eliminate the "flash of wrong theme".
    -->
    <script>
        (function () {
            var stored = localStorage.getItem('kinarahub_theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (stored === null && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <style>
        /* Smooth colour transitions across all elements when toggling dark mode */
        *, *::before, *::after {
            transition-property: color, background-color, border-color, box-shadow;
            transition-duration: 200ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }
        /* Suppress transitions on page load to prevent flicker */
        .no-transition, .no-transition * {
            transition: none !important;
        }
        /* Sidebar scrollbar styling */
        #app-sidebar::-webkit-scrollbar { width: 4px; }
        #app-sidebar::-webkit-scrollbar-track { background: transparent; }
        #app-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
        /* Toast animation */
        @keyframes slideIn {
            from { transform: translateX(110%); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0);    opacity: 1; }
            to   { transform: translateX(110%); opacity: 0; }
        }
        .toast-enter { animation: slideIn 0.3s ease forwards; }
        .toast-exit  { animation: slideOut 0.3s ease forwards; }
    </style>
</head>

<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

<?php // ---- Impersonation banner (admin browsing a store as read-only) ----
if (!empty($_SESSION['impersonate_store_id'])):
    $impersonatedName = htmlspecialchars($_SESSION['impersonate_store_name'] ?? 'Store', ENT_QUOTES, 'UTF-8');
?>
<div class="sticky top-0 z-50 flex items-center justify-between gap-4 bg-amber-500 px-4 py-2 text-sm font-medium text-white shadow-md" role="alert">
    <span class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Viewing as <strong class="ml-1"><?= $impersonatedName ?></strong>&nbsp;&mdash; Read Only
    </span>
    <form method="POST" action="/kinarahub/admin/exit-impersonate" class="flex-shrink-0">
        <?= \App\Middleware\CsrfMiddleware::field() ?>
        <button type="submit" class="rounded-md bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-700 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
            Exit
        </button>
    </form>
</div>
<?php endif; ?>

<?php
    // Resolve variables with defaults
    $pageTitle    = $pageTitle ?? 'Kinara Store Hub';
    $breadcrumb   = $breadcrumb ?? [];
    $currentPath  = $currentPath ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $storeName    = htmlspecialchars($_SESSION['store_name'] ?? 'My Store', ENT_QUOTES, 'UTF-8');
    $staffName    = htmlspecialchars($_SESSION['staff_name'] ?? 'Staff', ENT_QUOTES, 'UTF-8');
    $storeLogo    = $_SESSION['store_logo'] ?? '';
    $storeInitial = strtoupper(mb_substr($_SESSION['store_name'] ?? 'K', 0, 1));

    // Nav structure — [label, href, icon_svg, permission_key_placeholder]
    $navItems = [
        [
            'label'      => 'Dashboard',
            'href'       => '/kinarahub/dashboard',
            'permission' => null, // visible to all
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>',
        ],
        [
            'label'      => 'Inventory',
            'href'       => '/kinarahub/inventory',
            'permission' => 'inventory',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>',
        ],
        [
            'label'      => 'POS / New Sale',
            'href'       => '/kinarahub/pos',
            'permission' => 'sales',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>',
        ],
        [
            'label'      => 'Sales History',
            'href'       => '/kinarahub/sales',
            'permission' => 'sales',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>',
        ],
        [
            'label'      => 'Customers',
            'href'       => '/kinarahub/customers',
            'permission' => 'customers',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>',
        ],
        [
            'label'      => 'Reports',
            'href'       => '/kinarahub/reports',
            'permission' => 'reports',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>',
        ],
        [
            'label'      => 'Settings',
            'href'       => '/kinarahub/settings',
            'permission' => 'settings',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        ],
    ];

    // Helper to determine if a nav item is active
    function isNavActive(string $href, string $currentPath): bool {
        $hrefPath = parse_url($href, PHP_URL_PATH);
        if ($hrefPath === '/kinarahub/dashboard' || $hrefPath === '/kinarahub/') {
            return rtrim($currentPath, '/') === rtrim($hrefPath, '/');
        }
        return str_starts_with(rtrim($currentPath, '/'), rtrim($hrefPath, '/'));
    }
?>

<!-- =========================================================
     APP SHELL
     ========================================================= -->
<div class="flex h-full">

    <!-- =====================================================
         MOBILE SIDEBAR BACKDROP (hidden on lg+)
         ===================================================== -->
    <div
        id="sidebar-backdrop"
        class="fixed inset-0 z-30 bg-gray-900/60 backdrop-blur-sm transition-opacity duration-300 lg:hidden hidden"
        aria-hidden="true"
    ></div>

    <!-- =====================================================
         SIDEBAR (fixed, w-64; off-canvas on mobile)
         ===================================================== -->
    <aside
        id="app-sidebar"
        class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-slate-900 overflow-y-auto transition-transform duration-300 -translate-x-full lg:translate-x-0 lg:z-30"
        aria-label="Main navigation"
    >
        <!-- ----- Store identity ----- -->
        <div class="flex items-center gap-3 px-4 py-5 border-b border-slate-700/60">
            <?php if (!empty($storeLogo)): ?>
                <img
                    src="<?= htmlspecialchars($storeLogo, ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= $storeName ?> logo"
                    class="h-9 w-9 rounded-lg object-cover ring-2 ring-brand-500/40 flex-shrink-0"
                >
            <?php else: ?>
                <div
                    class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white font-bold text-base select-none ring-2 ring-brand-400/30"
                    aria-hidden="true"
                >
                    <?= htmlspecialchars($storeInitial, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-white leading-tight"><?= $storeName ?></p>
                <p class="text-xs text-slate-400 leading-tight mt-0.5">Store Hub</p>
            </div>
            <!-- Close sidebar button (mobile only) -->
            <button
                id="sidebar-close"
                type="button"
                class="lg:hidden flex-shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                aria-label="Close navigation menu"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- ----- Primary navigation ----- -->
        <nav class="flex-1 px-3 py-4 space-y-0.5" aria-label="Sidebar navigation">
            <?php
            /*
             * Phase 4 will wire real RBAC here.
             * For now every item with a non-null permission key is shown to all
             * authenticated users; the check is a placeholder comment.
             */
            foreach ($navItems as $item):
                $isActive = isNavActive($item['href'], $currentPath);
                $activeClass = $isActive
                    ? 'bg-brand-600/20 text-white ring-1 ring-inset ring-brand-500/30'
                    : 'text-slate-300 hover:bg-slate-800 hover:text-white';
            ?>
            <a
                href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
                class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150 <?= $activeClass ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>
            >
                <!-- Icon -->
                <span class="flex-shrink-0 <?= $isActive ? 'text-brand-400' : 'text-slate-400 group-hover:text-slate-200' ?> transition-colors duration-150">
                    <?= $item['icon'] ?>
                </span>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                <?php if ($isActive): ?>
                <span class="ml-auto h-1.5 w-1.5 rounded-full bg-brand-400" aria-hidden="true"></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- ----- Sidebar footer: staff info + logout ----- -->
        <div class="flex-shrink-0 border-t border-slate-700/60 px-3 py-4">
            <div class="flex items-center gap-3 px-2 py-2 rounded-lg">
                <!-- Staff avatar initials -->
                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-slate-700 text-slate-200 text-xs font-semibold select-none" aria-hidden="true">
                    <?= htmlspecialchars(strtoupper(mb_substr($_SESSION['staff_name'] ?? 'S', 0, 2)), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-200 leading-tight"><?= $staffName ?></p>
                    <p class="text-xs text-slate-500 leading-tight mt-0.5">
                        <?= htmlspecialchars(match((int)($_SESSION['role_id'] ?? 0)) {
                            1 => 'Owner',
                            2 => 'Manager',
                            3 => 'Staff',
                            default => 'Staff',
                        }, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <!-- Logout button -->
                <a
                    href="/kinarahub/logout"
                    class="flex-shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-slate-800 hover:text-red-400 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                    title="Sign out"
                    aria-label="Sign out"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                    </svg>
                </a>
            </div>
        </div>
    </aside>
    <!-- /sidebar -->

    <!-- =====================================================
         MAIN COLUMN (offset by sidebar width on lg+)
         ===================================================== -->
    <div class="flex flex-1 flex-col min-h-full lg:ml-64">

        <!-- ================================================
             TOP HEADER BAR
             ================================================ -->
        <header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm px-4 sm:px-6 py-3" aria-label="Top navigation bar">

            <!-- Mobile hamburger + Breadcrumb -->
            <div class="flex items-center gap-3 min-w-0">
            <!-- Mobile menu button (hidden on lg+) -->
            <button
                id="sidebar-toggle"
                type="button"
                class="lg:hidden rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                aria-label="Open navigation menu"
                aria-expanded="false"
                aria-controls="app-sidebar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>

            <!-- Breadcrumb -->
            <nav aria-label="Breadcrumb" class="flex items-center min-w-0">
                <ol class="flex items-center gap-1.5 text-sm" role="list">
                    <li>
                        <a href="/kinarahub/dashboard" class="text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                            </svg>
                            <span class="sr-only">Home</span>
                        </a>
                    </li>
                    <?php foreach ($breadcrumb as $idx => $crumb): ?>
                    <li class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 flex-shrink-0 text-gray-300 dark:text-gray-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                        </svg>
                        <?php if ($idx === array_key_last($breadcrumb)): ?>
                        <span class="truncate font-medium text-gray-700 dark:text-gray-200" aria-current="page">
                            <?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php else: ?>
                        <a
                            href="<?= htmlspecialchars($crumb['url'] ?? '#', ENT_QUOTES, 'UTF-8') ?>"
                            class="truncate text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400 transition-colors duration-150"
                        >
                            <?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
            </div><!-- /hamburger + breadcrumb wrapper -->

            <!-- Right-side controls -->
            <div class="flex items-center gap-3 flex-shrink-0">
                <!-- Store chip -->
                <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-brand-50 dark:bg-brand-900/30 px-3 py-1 text-xs font-medium text-brand-700 dark:text-brand-300 ring-1 ring-inset ring-brand-600/20 dark:ring-brand-400/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-500" aria-hidden="true"></span>
                    <?= $storeName ?>
                </span>

                <!-- Staff name (desktop) -->
                <span class="hidden md:block text-sm text-gray-600 dark:text-gray-400 font-medium"><?= $staffName ?></span>

                <!-- Dark mode toggle -->
                <button
                    id="dark-mode-toggle"
                    type="button"
                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-400 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                    aria-label="Toggle dark mode"
                    title="Toggle dark mode"
                >
                    <!-- Sun icon (shown in dark mode) -->
                    <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                    </svg>
                    <!-- Moon icon (shown in light mode) -->
                    <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                    </svg>
                </button>
            </div>
        </header>
        <!-- /header -->

        <!-- ================================================
             MAIN CONTENT AREA
             ================================================ -->
        <main
            id="main-content"
            class="flex-1 p-6"
            role="main"
            aria-label="Main content"
            tabindex="-1"
        >
            <?php
            if (!empty($view) && file_exists($view)):
                include $view;
            elseif (isset($content)):
                echo $content;
            else:
            ?>
            <!-- Placeholder when no view or content is provided -->
            <div class="flex items-center justify-center h-64">
                <p class="text-gray-400 dark:text-gray-600 text-sm">No content loaded.</p>
            </div>
            <?php endif; ?>
        </main>
        <!-- /main -->

    </div>
    <!-- /main column -->

</div>
<!-- /app shell -->

<!-- =========================================================
     TOAST CONTAINER
     ========================================================= -->
<?php include __DIR__ . '/../partials/toast.php'; ?>

<!-- =========================================================
     GLOBAL JAVASCRIPT
     ========================================================= -->
<script>
(function () {
    'use strict';

    // -------------------------------------------------------
    // Dark mode toggle
    // -------------------------------------------------------
    var toggleBtn = document.getElementById('dark-mode-toggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            var html = document.documentElement;
            var isDark = html.classList.toggle('dark');
            try {
                localStorage.setItem('kinarahub_theme', isDark ? 'dark' : 'light');
            } catch (_) {}
        });
    }

    // -------------------------------------------------------
    // Mobile sidebar toggle
    // -------------------------------------------------------
    var sidebar      = document.getElementById('app-sidebar');
    var backdrop     = document.getElementById('sidebar-backdrop');
    var sidebarBtn   = document.getElementById('sidebar-toggle');
    var sidebarOpen  = false;

    function openSidebar() {
        if (!sidebar || !backdrop) return;
        sidebarOpen = true;
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        backdrop.classList.remove('hidden');
        backdrop.classList.add('opacity-100');
        document.body.style.overflow = 'hidden';
        if (sidebarBtn) sidebarBtn.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
        if (!sidebar || !backdrop) return;
        sidebarOpen = false;
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
        backdrop.classList.remove('opacity-100');
        document.body.style.overflow = '';
        if (sidebarBtn) sidebarBtn.setAttribute('aria-expanded', 'false');
    }

    if (sidebarBtn) {
        sidebarBtn.addEventListener('click', function () {
            sidebarOpen ? closeSidebar() : openSidebar();
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }
    var sidebarCloseBtn = document.getElementById('sidebar-close');
    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', closeSidebar);
    }

    // Close mobile sidebar on lg breakpoint resize
    var mql = window.matchMedia('(min-width: 1024px)');
    mql.addEventListener('change', function (e) {
        if (e.matches && sidebarOpen) closeSidebar();
    });

    // -------------------------------------------------------
    // Modal helpers
    // -------------------------------------------------------

    /**
     * Opens a modal by ID.
     * Expects a <dialog> element or a div with role="dialog" and
     * data-modal="true".
     *
     * @param {string} id — ID of the modal element.
     */
    window.openModal = function (id) {
        var el = document.getElementById(id);
        if (!el) return;

        if (el instanceof HTMLDialogElement) {
            el.showModal();
        } else {
            el.removeAttribute('hidden');
            el.setAttribute('aria-hidden', 'false');
        }

        // Trap focus: move focus to the first focusable element inside the modal.
        requestAnimationFrame(function () {
            var focusable = el.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            if (focusable.length) focusable[0].focus();
        });
    };

    /**
     * Closes a modal by ID.
     *
     * @param {string} id — ID of the modal element.
     */
    window.closeModal = function (id) {
        var el = document.getElementById(id);
        if (!el) return;

        if (el instanceof HTMLDialogElement) {
            el.close();
        } else {
            el.setAttribute('hidden', '');
            el.setAttribute('aria-hidden', 'true');
        }
    };

    // -------------------------------------------------------
    // Keyboard shortcuts
    // -------------------------------------------------------
    document.addEventListener('keydown', function (e) {
        // Esc — close mobile sidebar first, then close the topmost open modal
        if (e.key === 'Escape') {
            if (sidebarOpen) {
                closeSidebar();
                e.preventDefault();
                return;
            }
            var dialogs = Array.from(
                document.querySelectorAll('dialog[open], [role="dialog"]:not([hidden]):not([aria-hidden="true"])')
            );
            if (dialogs.length) {
                var top = dialogs[dialogs.length - 1];
                if (top instanceof HTMLDialogElement) {
                    top.close();
                } else {
                    top.setAttribute('hidden', '');
                    top.setAttribute('aria-hidden', 'true');
                }
                e.preventDefault();
            }
        }

        // Ctrl+N / Cmd+N — trigger #btn-new-item if it exists
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            var btn = document.getElementById('btn-new-item');
            if (btn) {
                btn.click();
                e.preventDefault();
            }
        }
    });

    // -------------------------------------------------------
    // Suppress transition flicker on first load
    // -------------------------------------------------------
    document.body.classList.add('no-transition');
    window.addEventListener('load', function () {
        // A short delay ensures the browser has painted before we re-enable transitions
        setTimeout(function () {
            document.body.classList.remove('no-transition');
        }, 50);
    });

})();
</script>

</body>
</html>
