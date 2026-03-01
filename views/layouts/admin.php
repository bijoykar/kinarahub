<!DOCTYPE html>
<html lang="en" class="h-full">
<!--
  views/layouts/admin.php — Platform admin panel layout.

  A simpler layout than the store app.php — no sidebar, just a top nav bar.
  Used by all /admin/* pages except the login page.

  Expected variables (all optional with sensible defaults):
    $pageTitle    (string)  — <title> text. Default: 'Admin — Kinara Hub'
    $breadcrumb   (array)   — [['label'=>'...','url'=>'...'], ...] Default: []
    $content      (string)  — Pre-rendered HTML string to inject into <main>.
    $view         (string)  — Absolute path to a view file to include inside <main>.
    $currentPath  (string)  — Current URL path for nav active states.
-->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin — Kinara Hub', ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Favicon — distinct from store (red accent) -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%23dc2626'/><text y='22' x='6' font-size='18' fill='white' font-family='sans-serif' font-weight='bold'>A</text></svg>">

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
                        admin: {
                            50:  '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        },
                    },
                },
            },
        };
    </script>

    <!-- Dark-mode init -->
    <script>
        (function () {
            var stored = localStorage.getItem('kinarahub_admin_theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (stored === null && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <style>
        *, *::before, *::after {
            transition-property: color, background-color, border-color, box-shadow;
            transition-duration: 200ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }
        .no-transition, .no-transition * {
            transition: none !important;
        }
        [hidden] { display: none !important; }
    </style>
</head>

<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

<?php
    $pageTitle   = $pageTitle ?? 'Admin — Kinara Hub';
    $breadcrumb  = $breadcrumb ?? [];
    $currentPath = $currentPath ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    $adminName  = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
    $adminEmail = htmlspecialchars($_SESSION['admin_email'] ?? '', ENT_QUOTES, 'UTF-8');

    $navItems = [
        ['label' => 'Dashboard', 'href' => APP_URL . '/admin/dashboard'],
        ['label' => 'Stores',    'href' => APP_URL . '/admin/stores'],
    ];

    function adminIsNavActive(string $href, string $currentPath): bool {
        $hrefPath    = parse_url($href, PHP_URL_PATH);
        $appBasePath = rtrim((string)(parse_url(APP_URL, PHP_URL_PATH) ?? ''), '/');
        if ($hrefPath === $appBasePath . '/admin/dashboard') {
            return rtrim($currentPath, '/') === rtrim($hrefPath, '/');
        }
        return str_starts_with(rtrim($currentPath, '/'), rtrim($hrefPath, '/'));
    }
?>

<!-- =========================================================
     IMPERSONATION BANNER (when admin is viewing a store)
     ========================================================= -->
<?php if (!empty($_SESSION['impersonate_store_id'])): ?>
<div class="sticky top-0 z-50 flex items-center justify-between bg-amber-500 px-4 py-2 text-sm font-medium text-amber-950">
    <div class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Viewing <strong class="mx-1"><?= htmlspecialchars($_SESSION['impersonate_store_name'] ?? 'Store', ENT_QUOTES, 'UTF-8') ?></strong> &mdash; Read Only
    </div>
    <a href="<?= APP_URL ?>/admin/impersonate/exit" class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-700 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        Exit
    </a>
</div>
<?php endif; ?>

<!-- =========================================================
     TOP NAVIGATION BAR
     ========================================================= -->
<header class="sticky top-<?= !empty($_SESSION['impersonate_store_id']) ? '0' : '0' ?> z-40 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">

            <!-- Left: Logo + Nav -->
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <a href="<?= APP_URL ?>/admin/dashboard" class="flex items-center gap-2.5 group">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-admin-600 text-white font-bold text-sm shadow-sm shadow-admin-600/30 group-hover:bg-admin-700 transition-colors">
                        K
                    </div>
                    <div>
                        <span class="text-sm font-bold text-gray-900 dark:text-white leading-tight block">Kinara Hub</span>
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-admin-600 dark:text-admin-400 leading-tight">Admin</span>
                    </div>
                </a>

                <!-- Nav links -->
                <nav class="hidden sm:flex items-center gap-1" aria-label="Admin navigation">
                    <?php foreach ($navItems as $item):
                        $isActive = adminIsNavActive($item['href'], $currentPath);
                    ?>
                    <a
                        href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
                        class="rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 <?= $isActive
                            ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white' ?>"
                        <?= $isActive ? 'aria-current="page"' : '' ?>
                    >
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <!-- Right: Admin info + controls -->
            <div class="flex items-center gap-3">
                <!-- Admin badge -->
                <span class="hidden md:inline-flex items-center gap-1.5 rounded-full bg-admin-50 dark:bg-admin-900/30 px-3 py-1 text-xs font-medium text-admin-700 dark:text-admin-300 ring-1 ring-inset ring-admin-600/20 dark:ring-admin-400/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-admin-500" aria-hidden="true"></span>
                    <?= $adminName ?>
                </span>

                <!-- Dark mode toggle -->
                <button
                    id="admin-dark-toggle"
                    type="button"
                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-400 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-admin-500"
                    aria-label="Toggle dark mode"
                    title="Toggle dark mode"
                >
                    <svg id="adm-icon-sun" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                    <svg id="adm-icon-moon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                </button>

                <!-- Logout -->
                <a
                    href="<?= APP_URL ?>/admin/logout"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-red-600 dark:hover:text-red-400 transition-colors duration-150"
                    title="Sign out"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                    <span class="hidden sm:inline">Sign out</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile nav (visible below sm) -->
    <div class="sm:hidden border-t border-gray-200 dark:border-gray-700 px-4 py-2 flex items-center gap-1">
        <?php foreach ($navItems as $item):
            $isActive = adminIsNavActive($item['href'], $currentPath);
        ?>
        <a
            href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
            class="flex-1 text-center rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 <?= $isActive
                ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                : 'text-gray-500 dark:text-gray-400' ?>"
        >
            <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php endforeach; ?>
    </div>
</header>

<!-- =========================================================
     BREADCRUMB
     ========================================================= -->
<?php if (!empty($breadcrumb)): ?>
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-4">
    <nav aria-label="Breadcrumb">
        <ol class="flex items-center gap-1.5 text-sm" role="list">
            <li>
                <a href="<?= APP_URL ?>/admin/dashboard" class="text-gray-400 hover:text-admin-600 dark:hover:text-admin-400 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    <span class="sr-only">Home</span>
                </a>
            </li>
            <?php foreach ($breadcrumb as $idx => $crumb): ?>
            <li class="flex items-center gap-1.5">
                <svg class="h-4 w-4 flex-shrink-0 text-gray-300 dark:text-gray-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
                <?php if ($idx === array_key_last($breadcrumb)): ?>
                <span class="font-medium text-gray-700 dark:text-gray-200" aria-current="page"><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                <a href="<?= htmlspecialchars($crumb['url'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" class="text-gray-500 hover:text-admin-600 dark:text-gray-400 dark:hover:text-admin-400 transition-colors"><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>
</div>
<?php endif; ?>

<!-- =========================================================
     MAIN CONTENT AREA
     ========================================================= -->
<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6" role="main" aria-label="Main content" tabindex="-1">
    <?php
    if (!empty($view) && file_exists($view)):
        include $view;
    elseif (isset($content)):
        echo $content;
    else:
    ?>
    <div class="flex items-center justify-center h-64">
        <p class="text-gray-400 dark:text-gray-600 text-sm">No content loaded.</p>
    </div>
    <?php endif; ?>
</main>

<!-- =========================================================
     FOOTER
     ========================================================= -->
<footer class="border-t border-gray-200 dark:border-gray-700 mt-auto">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4">
        <p class="text-center text-xs text-gray-400 dark:text-gray-600">
            &copy; <?= date('Y') ?> Kinara Store Hub &mdash; Platform Admin
        </p>
    </div>
</footer>

<!-- =========================================================
     GLOBAL JAVASCRIPT
     ========================================================= -->
<script>
(function () {
    'use strict';

    // Dark mode toggle
    var toggleBtn = document.getElementById('admin-dark-toggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            var html = document.documentElement;
            var isDark = html.classList.toggle('dark');
            try {
                localStorage.setItem('kinarahub_admin_theme', isDark ? 'dark' : 'light');
            } catch (_) {}
        });
    }

    // Suppress transition flicker on load
    document.body.classList.add('no-transition');
    window.addEventListener('load', function () {
        setTimeout(function () {
            document.body.classList.remove('no-transition');
        }, 50);
    });
})();
</script>

</body>
</html>
