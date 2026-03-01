<?php
/**
 * views/partials/sidebar.php — Application sidebar partial.
 *
 * Renders the fixed left navigation sidebar.  Included by views/layouts/app.php
 * but can be included independently when needed.
 *
 * Expected variables (all optional with defaults):
 *   $currentPath  (string) — Current URL path for active-link detection.
 *                            Default: derived from $_SERVER['REQUEST_URI'].
 *   $storeName    (string) — Store display name.    Default: 'My Store'
 *   $storeLogo    (string) — URL to store logo.     Default: '' (initials shown)
 *   $staffName    (string) — Logged-in staff name.  Default: 'Staff'
 *
 * Session variables consumed:
 *   $_SESSION['store_name']
 *   $_SESSION['staff_name']
 *   $_SESSION['role_id']
 *   $_SESSION['store_logo']
 *
 * Active-state logic:
 *   An item is "active" when the current path starts with the item's href path.
 *   The dashboard item uses an exact match to prevent it lighting up everywhere.
 *
 * Permission filtering (Phase 4 placeholder):
 *   The $navItems array carries a 'permission' key.  Currently all items are
 *   rendered.  In Phase 4 replace the comment block below with a real call to
 *   App\Helpers\Permission::can($permission).
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Resolve variables
// ---------------------------------------------------------------------------
$currentPath  = $currentPath  ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$storeName    = htmlspecialchars($_SESSION['store_name'] ?? 'My Store', ENT_QUOTES, 'UTF-8');
$staffName    = htmlspecialchars($_SESSION['staff_name'] ?? 'Staff', ENT_QUOTES, 'UTF-8');
$storeLogo    = $_SESSION['store_logo'] ?? '';
$storeInitial = strtoupper(mb_substr($_SESSION['store_name'] ?? 'K', 0, 1));
$roleId       = (int)($_SESSION['role_id'] ?? 0);

$roleLabel = match ($roleId) {
    1 => 'Owner',
    2 => 'Manager',
    3 => 'Staff',
    default => 'Staff',
};

// ---------------------------------------------------------------------------
// Navigation items
// Each entry: label, href, permission key (null = always visible), icon SVG
// ---------------------------------------------------------------------------
$navGroups = [
    'Overview' => [
        [
            'label'      => 'Dashboard',
            'href'       => '/kinarahub/dashboard',
            'permission' => null,
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>',
        ],
    ],
    'Store' => [
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
    ],
    'Analytics' => [
        [
            'label'      => 'Reports',
            'href'       => '/kinarahub/reports',
            'permission' => 'reports',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>',
        ],
    ],
    'System' => [
        [
            'label'      => 'Settings',
            'href'       => '/kinarahub/settings',
            'permission' => 'settings',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        ],
    ],
];

/**
 * Determines whether a navigation item's href matches the current path.
 *
 * @param  string $href        The nav item's href value.
 * @param  string $currentPath The current request path.
 * @return bool
 */
function sidebarIsActive(string $href, string $currentPath): bool
{
    $hrefPath = rtrim(parse_url($href, PHP_URL_PATH) ?? '', '/');
    $reqPath  = rtrim($currentPath, '/');

    // Dashboard: exact match only
    if ($hrefPath === '/kinarahub/dashboard' || $hrefPath === '/kinarahub') {
        return $reqPath === $hrefPath || $reqPath === '/kinarahub';
    }

    // Other items: prefix match
    return str_starts_with($reqPath, $hrefPath);
}
?>

<aside
    id="app-sidebar"
    class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-slate-900 overflow-y-auto"
    aria-label="Main navigation"
>
    <!-- -------------------------------------------------------
         Store identity header
         ------------------------------------------------------- -->
    <div class="flex items-center gap-3 px-4 py-5 border-b border-slate-700/60 flex-shrink-0">
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
            <p class="truncate text-sm font-semibold text-white leading-tight">
                <?= $storeName ?>
            </p>
            <p class="text-xs text-slate-400 leading-tight mt-0.5">Kinara Store Hub</p>
        </div>
    </div>

    <!-- -------------------------------------------------------
         Primary navigation (grouped)
         ------------------------------------------------------- -->
    <nav class="flex-1 px-3 py-4 space-y-5" aria-label="Sidebar navigation">
        <?php foreach ($navGroups as $groupLabel => $items): ?>
        <div>
            <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-slate-500 select-none">
                <?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?>
            </p>
            <ul role="list" class="space-y-0.5">
                <?php foreach ($items as $item):
                    /*
                     * Phase 4 permission check — placeholder.
                     * Replace the 'true' below with:
                     *   App\Helpers\Permission::can($item['permission'])
                     * to enforce role-based visibility.
                     */
                    $hasPermission = true; // Phase 4: replace with real check

                    if (!$hasPermission) continue;

                    $isActive   = sidebarIsActive($item['href'], $currentPath);
                    $linkClass  = $isActive
                        ? 'bg-brand-600/20 text-white ring-1 ring-inset ring-brand-500/30'
                        : 'text-slate-300 hover:bg-slate-800 hover:text-white';
                    $iconClass  = $isActive
                        ? 'text-brand-400'
                        : 'text-slate-400 group-hover:text-slate-200';
                ?>
                <li>
                    <a
                        href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
                        class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150 <?= $linkClass ?>"
                        <?= $isActive ? 'aria-current="page"' : '' ?>
                    >
                        <span class="flex-shrink-0 transition-colors duration-150 <?= $iconClass ?>">
                            <?= $item['icon'] ?>
                        </span>
                        <span class="truncate"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($isActive): ?>
                        <span class="ml-auto h-1.5 w-1.5 flex-shrink-0 rounded-full bg-brand-400" aria-hidden="true"></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- -------------------------------------------------------
         Sidebar footer: staff info + logout
         ------------------------------------------------------- -->
    <div class="flex-shrink-0 border-t border-slate-700/60 px-3 py-4">
        <div class="flex items-center gap-3 rounded-lg px-2 py-2">
            <!-- Staff avatar (initials) -->
            <div
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-slate-700 text-slate-200 text-xs font-semibold uppercase select-none"
                aria-hidden="true"
            >
                <?= htmlspecialchars(strtoupper(mb_substr($_SESSION['staff_name'] ?? 'S', 0, 2)), ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-200 leading-tight">
                    <?= $staffName ?>
                </p>
                <p class="text-xs text-slate-500 leading-tight mt-0.5">
                    <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <!-- Logout -->
            <a
                href="/kinarahub/logout"
                class="flex-shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-slate-800 hover:text-red-400 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                title="Sign out"
                aria-label="Sign out from <?= $storeName ?>"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                </svg>
            </a>
        </div>
    </div>
</aside>
