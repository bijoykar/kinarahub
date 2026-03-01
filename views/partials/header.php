<?php
/**
 * views/partials/header.php — Top application header bar partial.
 *
 * Renders the sticky top header that sits above the main content area.
 * Included by views/layouts/app.php but can be included independently.
 *
 * Expected variables (all optional with defaults):
 *   $breadcrumb  (array)  — [['label' => '...', 'url' => '...'], ...]
 *                            Last item is rendered as plain text (aria-current).
 *                            Default: []
 *
 * Session variables consumed:
 *   $_SESSION['store_name']   — Displayed in the store name chip.
 *   $_SESSION['staff_name']   — Displayed as staff identity.
 *
 * Emitted elements:
 *   - Breadcrumb navigation
 *   - Store name chip
 *   - Logged-in staff name
 *   - Dark mode toggle button (triggers JS defined in app.php layout)
 *   - Logout link
 */

declare(strict_types=1);

$breadcrumb = $breadcrumb ?? [];
$storeName  = htmlspecialchars($_SESSION['store_name'] ?? 'My Store', ENT_QUOTES, 'UTF-8');
$staffName  = htmlspecialchars($_SESSION['staff_name'] ?? 'Staff', ENT_QUOTES, 'UTF-8');
?>

<header
    class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm px-6 py-3"
    aria-label="Top navigation bar"
>
    <!-- =======================================================
         LEFT: Breadcrumb navigation
         ======================================================= -->
    <nav aria-label="Breadcrumb" class="flex min-w-0 items-center">
        <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm" role="list">

            <!-- Home icon (always first) -->
            <li>
                <a
                    href="/kinarahub/dashboard"
                    class="flex items-center text-gray-400 hover:text-brand-600 dark:text-gray-500 dark:hover:text-brand-400 transition-colors duration-150 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                    aria-label="Dashboard home"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                </a>
            </li>

            <?php foreach ($breadcrumb as $idx => $crumb):
                $isLast  = ($idx === array_key_last($breadcrumb));
                $label   = htmlspecialchars($crumb['label'] ?? '', ENT_QUOTES, 'UTF-8');
                $url     = htmlspecialchars($crumb['url']   ?? '#', ENT_QUOTES, 'UTF-8');
            ?>
            <!-- Chevron separator -->
            <li aria-hidden="true" class="flex items-center">
                <svg class="h-4 w-4 flex-shrink-0 text-gray-300 dark:text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                </svg>
            </li>

            <!-- Crumb link or current page label -->
            <li>
                <?php if ($isLast): ?>
                <span
                    class="max-w-[200px] truncate font-medium text-gray-800 dark:text-gray-100"
                    aria-current="page"
                    title="<?= $label ?>"
                >
                    <?= $label ?>
                </span>
                <?php else: ?>
                <a
                    href="<?= $url ?>"
                    class="max-w-[160px] truncate text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400 transition-colors duration-150 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                    title="<?= $label ?>"
                >
                    <?= $label ?>
                </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>

        </ol>
    </nav>

    <!-- =======================================================
         RIGHT: Store chip, staff name, dark toggle, logout
         ======================================================= -->
    <div class="flex flex-shrink-0 items-center gap-2 sm:gap-3">

        <!-- Store name chip -->
        <span
            class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-brand-50 dark:bg-brand-900/30 px-3 py-1 text-xs font-medium text-brand-700 dark:text-brand-300 ring-1 ring-inset ring-brand-600/20 dark:ring-brand-400/20 select-none"
            title="Active store"
        >
            <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-brand-500 animate-pulse" aria-hidden="true"></span>
            <?= $storeName ?>
        </span>

        <!-- Staff name (desktop only) -->
        <span class="hidden md:block text-sm font-medium text-gray-600 dark:text-gray-400 select-none max-w-[120px] truncate" title="<?= $staffName ?>">
            <?= $staffName ?>
        </span>

        <!-- Divider -->
        <div class="hidden sm:block h-5 w-px bg-gray-200 dark:bg-gray-700" aria-hidden="true"></div>

        <!-- Dark mode toggle -->
        <button
            id="dark-mode-toggle"
            type="button"
            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
            aria-label="Toggle dark mode"
            title="Toggle dark mode (current: light)"
        >
            <!-- Sun icon: shown when dark mode is active -->
            <svg
                id="icon-sun"
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 hidden dark:block"
                fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.75"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
            </svg>
            <!-- Moon icon: shown when light mode is active -->
            <svg
                id="icon-moon"
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 block dark:hidden"
                fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.75"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
            </svg>
        </button>

        <!-- Logout link -->
        <a
            href="/kinarahub/logout"
            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-900/20 dark:hover:text-red-400 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
            aria-label="Sign out"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
            </svg>
            <span class="hidden sm:inline">Sign out</span>
        </a>

    </div>
    <!-- /right controls -->

</header>
