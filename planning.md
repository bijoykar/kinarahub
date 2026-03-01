# Kinara Store Hub — Phase-wise Build Plan

> Based on: `spec.md` v1.0 | Plan Date: 2026-03-01

Each phase has clear entry criteria (what must exist before starting), deliverables (what gets built), and exit criteria (how to verify it's done before moving on).

---

## Phase Overview

| # | Phase | Layer | Depends On |
|---|---|---|---|
| 1 | Foundation & Project Scaffold | All | — |
| 2 | Database Schema | DB | Phase 1 |
| 3 | Auth — Store Registration & Login | Backend + Frontend | Phase 2 |
| 4 | RBAC — Roles, Staff & Permissions | Backend + Frontend | Phase 3 |
| 5 | Inventory Management | Backend + Frontend | Phase 4 |
| 6 | Sales Management | Backend + Frontend | Phase 5 |
| 7 | Customer & Credit Management | Backend + Frontend | Phase 6 |
| 8 | Dashboard | Backend + Frontend | Phase 6 |
| 9 | Reports & Exports | Backend + Frontend | Phase 8 |
| 10 | REST API (v1) | Backend | Phase 5–7 |
| 11 | Platform Admin Panel | Backend + Frontend | Phase 3 |
| 12 | Receipts & PDF Invoices | Backend + Frontend | Phase 6 |
| 13 | Android App | Mobile | Phase 10 |
| 14 | iOS App | Mobile | Phase 10 |
| 15 | Polish, Security & Hardening | All | Phase 9–12 |

---

## Phase 1 — Foundation & Project Scaffold

**Goal:** Working skeleton that all future phases build on. No features, just structure.

### Deliverables

#### Directory Structure
```
kinarahub/
├── public/
│   └── index.php              ← front controller (all web requests)
├── app/
│   ├── Core/
│   │   ├── Router.php         ← URL routing (GET/POST/PUT/DELETE)
│   │   ├── Request.php        ← wraps $_GET, $_POST, $_FILES, body
│   │   ├── Response.php       ← send JSON or render view
│   │   ├── TenantScope.php    ← injects store_id into all DB queries
│   │   └── Database.php       ← PDO singleton; reads db_connection for isolation
│   ├── Middleware/
│   │   ├── AuthMiddleware.php ← checks session or JWT; redirects if unauthenticated
│   │   ├── CsrfMiddleware.php ← validates CSRF token on POST
│   │   └── PermissionMiddleware.php ← checks role_permissions for current user
│   ├── Controllers/           ← thin; validate + call service + return response
│   ├── Services/              ← business logic
│   ├── Models/                ← PDO queries; all scoped via TenantScope
│   └── Helpers/
│       ├── Jwt.php            ← sign, verify, decode JWT (HS256)
│       ├── Paginator.php      ← offset pagination helper
│       ├── CsvParser.php      ← parse uploaded CSV into rows
│       └── Mailer.php         ← PHPMailer wrapper for transactional email
├── api/
│   └── v1/
│       └── index.php          ← API front controller
├── admin/
│   └── index.php              ← Admin panel front controller
├── config/
│   ├── app.php                ← constants: timezone, currency, pagination defaults
│   ├── db.php                 ← PDO connection factory
│   └── routes.php             ← all route definitions
├── views/
│   ├── layouts/
│   │   ├── app.php            ← main shell (sidebar + header)
│   │   └── auth.php           ← login/register shell (no sidebar)
│   └── partials/
│       ├── sidebar.php
│       ├── header.php
│       └── toast.php
├── uploads/                   ← logos, CSVs (outside webroot ideally)
├── migrations/                ← numbered SQL files (001_initial.sql, etc.)
├── .env                       ← DB creds, JWT secret, mail config (not committed)
├── .env.example               ← template with all required keys, no real values
└── .htaccess                  ← mod_rewrite: all requests → public/index.php
```

#### Core Files
- `config/app.php` — `define('TIMEZONE', 'Asia/Kolkata')`, `define('CURRENCY', 'INR')`, `define('PER_PAGE', 20)`
- `.env.example` — keys: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `JWT_SECRET`, `MAIL_HOST`, `MAIL_USER`, `MAIL_PASS`, `APP_URL`
- `public/.htaccess` — rewrite all traffic to `index.php`
- `Router.php` — register GET/POST/PUT/DELETE routes; extract URL params; call middleware chain before controller
- `TenantScope.php` — `addScope(PDOStatement $stmt, int $storeId)` appends `AND store_id = ?` to queries; swappable for DB resolver in future

### Exit Criteria
- `http://localhost/kinarahub/` loads without PHP errors
- Router correctly dispatches a test route
- PDO connects to MySQL using `.env` credentials

---

## Phase 2 — Database Schema

**Goal:** All 14 tables created, indexed, and seeded with lookup data.

### Deliverables

#### Migration Files
- `migrations/001_stores_staff_roles.sql` — stores, staff, roles, role_permissions, role_field_restrictions, refresh_tokens
- `migrations/002_inventory.sql` — categories, units_of_measure, products, product_variants
- `migrations/003_sales.sql` — sales, sale_items
- `migrations/004_customers.sql` — customers, customer_credits, credit_payments
- `migrations/005_seed_uom.sql` — insert 8 UOM records (Pieces, Kg, Gram, Litre, ML, Box, Pack, Dozen)

#### Schema Runner Script
- `migrations/run.php` — reads all numbered `.sql` files in order, executes each, logs result

### Exit Criteria
- Run `php migrations/run.php` → all tables created with zero errors
- `SHOW INDEXES FROM products` shows `store_id`, `(store_id, category_id)`, `(store_id, status)` indexes
- `DESCRIBE sales` shows all required columns

---

## Phase 3 — Auth: Store Registration & Login

**Goal:** A store can register, verify email, log in, and log out. Session is established with store_id, user_id, role_id.

### Deliverables

#### Backend
- `StoreController.php` — `register()`, `verifyEmail($token)`, `login()`, `logout()`
- `StoreService.php` — `register($data)`: hash password, create store (pending_verification), generate email token, send verification email via Mailer; `verifyEmail($token)`: activate store, create Owner role, seed Walk-in Customer; `login($email, $pass)`: verify, set session
- `AuthMiddleware.php` — redirect to `/login` if session not set; attach store_id to all subsequent requests

#### Frontend Views
- `views/auth/register.php` — registration form (store name, owner name, email, mobile, password)
- `views/auth/verify-pending.php` — "Check your email" confirmation page
- `views/auth/login.php` — email + password form
- `views/auth/setup.php` — post-verification: upload logo, enter address (optional, can skip)
- `views/errors/404.php`, `views/errors/403.php`

#### Routes
```
GET  /register         → StoreController::showRegister
POST /register         → StoreController::register
GET  /verify/:token    → StoreController::verifyEmail
GET  /login            → StoreController::showLogin
POST /login            → StoreController::login
POST /logout           → StoreController::logout
GET  /setup            → StoreController::showSetup  [auth required]
POST /setup            → StoreController::saveSetup  [auth required]
```

### Exit Criteria
- Register → receive email → click link → account active → redirected to setup
- Login with wrong password → error message shown
- Logged-in session persists across pages
- Logout destroys session and redirects to /login
- Direct access to `/dashboard` while logged out redirects to `/login`

---

## Phase 4 — RBAC: Roles, Staff & Permissions

**Goal:** Owner can create roles, assign per-module CRUD permissions and field restrictions, and invite staff members.

### Deliverables

#### Backend
- `RoleController.php` — CRUD for roles
- `RoleService.php` — create role with default deny-all permissions; update permissions; update field restrictions
- `StaffController.php` — list, create, edit, deactivate staff
- `StaffService.php` — create staff with hashed password; assign role
- `PermissionMiddleware.php` — reads `role_permissions` for current user's role; blocks action if not allowed; strips restricted fields from responses

#### Frontend Views
- `views/settings/roles/index.php` — list all roles with action counts
- `views/settings/roles/edit.php` — permission matrix (module × action checkboxes) + field restriction toggles
- `views/settings/staff/index.php` — staff list with role badge and status
- `views/settings/staff/form.php` — create/edit staff modal

#### Routes
```
GET  /settings/roles              → list
GET  /settings/roles/create       → form
POST /settings/roles              → create
GET  /settings/roles/:id/edit     → form
POST /settings/roles/:id          → update
POST /settings/roles/:id/delete   → delete (cannot delete Owner role)

GET  /settings/staff              → list
POST /settings/staff              → create
POST /settings/staff/:id          → update
POST /settings/staff/:id/deactivate → deactivate
```

### Exit Criteria
- Owner creates "Cashier" role with only `sales.create` and `sales.read` allowed
- Cashier logs in → cannot access `/inventory` (403 page shown)
- Owner hides `cost_price` for Cashier role → Cashier's inventory view shows no cost price column
- Cannot delete Owner role

---

## Phase 5 — Inventory Management

**Goal:** Full product CRUD with variants, categories, UOM, stock alerts, CSV import/export.

### Deliverables

#### Backend
- `ProductController.php` — list (paginated, filterable), create, edit, delete, CSV import, CSV export
- `ProductService.php` — upsert logic for CSV; stock status computation; validate SKU uniqueness per store
- `ProductModel.php` — all PDO queries via TenantScope; optimistic lock on UPDATE (check version)
- `CategoryController.php` — simple CRUD (inline modal, no separate page needed)
- `CsvParser.php` — parse CSV, validate rows, return `[inserted, updated, failed]` summary

#### Frontend Views
- `views/inventory/index.php` — table with columns: SKU, name, category, UOM, selling price, cost price (role-gated), stock qty, reorder point, status badge (green/amber/red), actions
- `views/inventory/index.php` includes:
  - Add/Edit modal (all product fields + variant section)
  - Delete with 5-second undo toast
  - Filter bar: category, status, search by name/SKU
  - Pagination footer
  - "Import CSV" button → file upload modal with template download link
  - "Export CSV" button
  - CSV import result modal (shows inserted/updated/failed summary)

#### Routes
```
GET  /inventory                → list (paginated)
POST /inventory                → create product
POST /inventory/:id            → update product
POST /inventory/:id/delete     → delete (soft: set status=inactive, or hard delete)
POST /inventory/:id/variants   → add variant
POST /inventory/import         → CSV import
GET  /inventory/export         → CSV download
```

### Exit Criteria
- Add product → appears in list with correct stock badge
- Upload CSV with 10 rows (3 new, 5 updates, 2 invalid) → summary shows 3/5/2
- SKU auto-uppercased when typing
- Delete product → undo toast appears; clicking undo keeps the item; waiting 5s removes it
- Out of stock product shows red badge; low stock shows amber
- Cashier role (no inventory.create) sees no "Add Product" button

---

## Phase 6 — Sales Management

**Goal:** Both POS and bookkeeping entry modes work end to end. Stock decrements correctly on sale.

### Deliverables

#### Backend
- `SaleController.php` — POS create, bookkeeping create, list (paginated), detail
- `SaleService.php` — atomic transaction: insert sale → insert sale_items → decrement stock (optimistic lock) → create customer_credit if credit payment; generate sale_number (INV-XXXXX per store)
- `SaleModel.php` — all queries TenantScope-scoped

#### Frontend Views
- `views/sales/pos.php` — POS screen:
  - Product search bar (name or SKU, live filter)
  - Cart table (product, qty spinner, unit price, line total, remove)
  - Totals sidebar (subtotal, tax, total)
  - Payment method selector (Cash / UPI / Card / Credit)
  - Customer selector (appears only when Credit selected; autocomplete from customers list; "New Customer" inline form)
  - Submit button → confirm → sale saved → redirect to receipt
- `views/sales/bookkeeping.php` — manual entry form:
  - Date picker (can backdate)
  - Line items (add row: product search, qty, price)
  - Payment method
  - Optional customer
  - Notes
- `views/sales/index.php` — sales history table (date, sale#, customer, payment method, total, actions)
- `views/sales/detail.php` — sale detail with line items; links to receipt and invoice

#### Routes
```
GET  /sales                  → history list
GET  /sales/pos              → POS screen
POST /sales/pos              → create POS sale
GET  /sales/bookkeeping      → bookkeeping form
POST /sales/bookkeeping      → create bookkeeping sale
GET  /sales/:id              → sale detail
GET  /sales/:id/receipt      → printable thermal receipt
GET  /sales/:id/invoice.pdf  → PDF invoice download
```

### Exit Criteria
- POS: add 3 items, select UPI, submit → stock decrements by correct qty for each item
- POS: select Credit, no customer selected → validation error "Customer required for credit sales"
- Bookkeeping: backdate sale to yesterday → sale_date stored correctly
- Optimistic lock: manually edit DB version to 999, try to record a sale that touches that product → 409 error, transaction rolled back
- Sale number format: first sale = INV-00001; second = INV-00002

---

## Phase 7 — Customer & Credit Management

**Goal:** Named customers tracked; credit dues recorded; partial payments logged.

### Deliverables

#### Backend
- `CustomerController.php` — list, create, edit, view credit history
- `CustomerService.php` — create customer; record credit payment (partial or full); update `outstanding_balance`
- `CustomerModel.php` — queries for outstanding balance, credit history per customer

#### Frontend Views
- `views/customers/index.php` — table: name, mobile, outstanding balance (highlighted red if > 0), actions
- `views/customers/detail.php` — customer profile + credit history table (sale ref, amount due, paid, balance, due date) + "Record Payment" button
- "Record Payment" modal: amount, payment method, notes; updates balance live on submit

#### Routes
```
GET  /customers              → list
POST /customers              → create
GET  /customers/:id          → detail + credit history
POST /customers/:id/payments → record payment against dues
```

### Exit Criteria
- Credit sale of ₹500 → customer outstanding_balance = ₹500
- Record partial payment of ₹200 → outstanding_balance = ₹300; credit_payments row created
- Customer with zero balance shows no red highlight
- Walk-in Customer does not appear in customer list (is_default = 1 filtered out)

---

## Phase 8 — Dashboard

**Goal:** Real-time KPI widgets and three Chart.js charts on a modern, responsive dashboard.

### Deliverables

#### Backend
- `DashboardController.php` — single action that runs all aggregate queries
- `DashboardService.php` — queries for: today's revenue, week/month revenue, stock value, out-of-stock count, low-stock count, top 5 products today, recent 10 sales, sales trend (day/week/month/year), payment method breakdown

#### Frontend Views
- `views/dashboard/index.php`:
  - 8 KPI stat cards (2-column grid on desktop, stacked on tablet)
  - Sales Trend line chart (Chart.js) with period toggle buttons (Day / Week / Month / Year); data fetched via AJAX `GET /dashboard/chart-data?period=week`
  - Sales by Payment Method donut chart
  - Stock Status Distribution donut chart
  - Top 5 products mini table
  - Recent Sales mini table (last 10, with payment method badge)

#### Additional Route
```
GET /dashboard                          → main dashboard view
GET /dashboard/chart-data?period=:p     → JSON: { labels[], datasets[] } for Chart.js
```

### Exit Criteria
- Dashboard loads in < 2 seconds (all queries indexed on sale_date + store_id)
- Clicking "Week" on the chart re-fetches and re-renders without page reload
- Out of Stock widget shows correct count; clicking it navigates to `/inventory?status=out_of_stock`
- Dark mode: all chart colors, card backgrounds, and text readable in dark theme

---

## Phase 9 — Reports & Exports

**Goal:** 5 named reports, all filterable by date range, all exportable as PDF. Additional CSV exports.

### Deliverables

#### Backend
- `ReportController.php` — one action per report + export routes
- `ReportService.php`:
  - `topSellers($storeId, $from, $to)` — GROUP BY product, SUM qty and revenue
  - `inventoryAging($storeId, $days)` — products with no sale_items in last N days
  - `profitAndLoss($storeId, $from, $to)` — revenue, COGS, gross profit; breakdown by category
  - `customerDues($storeId)` — customers with outstanding_balance > 0
  - `gstSummary($storeId, $from, $to)` — total sales, total tax_amount
- `PdfExporter.php` — mPDF/TCPDF wrapper; takes report name + data array → generates branded PDF

#### Frontend Views
- `views/reports/index.php` — report selection landing (5 report cards with icon + description)
- `views/reports/top-sellers.php` — date range picker + results table + "Export PDF" + "Export CSV"
- `views/reports/aging.php` — days selector (30/60/90) + results table + "Export PDF"
- `views/reports/pnl.php` — date range + summary cards + category breakdown table + "Export PDF"
- `views/reports/customer-dues.php` — live table (no date filter) + "Export CSV"
- `views/reports/gst.php` — date range + totals + "Export PDF" + "Export CSV"

#### Routes
```
GET /reports                           → landing
GET /reports/top-sellers               → view (with ?from=&to= params)
GET /reports/top-sellers/export-pdf    → PDF download
GET /reports/top-sellers/export-csv    → CSV download
GET /reports/aging?days=30             → view
GET /reports/pnl                       → view
GET /reports/pnl/export-pdf            → PDF download
GET /reports/customer-dues             → view
GET /reports/customer-dues/export-csv  → CSV download
GET /reports/gst                       → view
GET /reports/gst/export-pdf            → PDF download
GET /reports/gst/export-csv            → CSV download
```

### Exit Criteria
- P&L report: revenue - COGS = gross profit (verified manually against test data)
- Aging report with 30-day filter: only shows products with no sales in last 30 days
- PDF export: opens in browser with store logo and store name in header
- GST summary CSV: opens in Excel with correct columns (period, total sales, total GST)
- Field restriction enforced: Cashier role cannot access `/reports` (403)

---

## Phase 10 — REST API (v1)

**Goal:** All core features accessible via JWT-authenticated REST API for mobile app consumption.

### Deliverables

#### Infrastructure
- `api/v1/index.php` — API front controller; no sessions; reads JWT from `Authorization` header; returns JSON only; catches all exceptions and returns `{ success: false, error: "..." }`
- `ApiAuthMiddleware.php` — decode + verify JWT; attach `store_id` and `staff_id` to request context
- `ApiResponse.php` — static helper: `success($data, $meta)`, `error($message, $code)`

#### API Controllers (in `app/Controllers/Api/`)
- `AuthApiController.php` — login, refresh, logout
- `ProductApiController.php` — list (paginated), show, create, update, delete, variants
- `SaleApiController.php` — create, list (paginated), show
- `CustomerApiController.php` — list, create, show credits, record payment
- `DashboardApiController.php` — summary stats

#### All Endpoints (from spec.md §11)
See spec.md Section 11 for full endpoint list.

#### Validation
- All POST/PUT inputs validated and return `422 Unprocessable Entity` with field errors on failure
- `store_id` never accepted in body — always from JWT payload

### Exit Criteria (test with curl or Postman)
```bash
# Login
POST /api/v1/auth/login { email, password } → 200 with access_token + refresh_token

# Authenticated request
GET /api/v1/products -H "Authorization: Bearer <token>" → 200 with paginated products

# Expired token
GET /api/v1/products -H "Authorization: Bearer <expired>" → 401

# Refresh
POST /api/v1/auth/refresh { refresh_token } → 200 with new access_token

# Missing field
POST /api/v1/products {} → 422 with field error list
```

---

## Phase 11 — Platform Admin Panel

**Goal:** Kinara Hub platform owner can manage all stores, view platform stats, and browse any store in read-only mode.

### Deliverables

#### Backend
- `admin/index.php` — separate front controller; no store-scoped session; reads `$_SESSION['admin_id']`
- `AdminAuthController.php` — admin login/logout (credentials seeded in DB or .env)
- `AdminStoreController.php` — list all stores, view detail, activate, suspend
- `AdminImpersonateController.php` — set `$_SESSION['impersonate_store_id']`; read-only flag prevents all writes

#### Frontend Views (in `views/admin/`)
- `views/admin/login.php` — admin login (different branding from store login)
- `views/admin/dashboard.php` — platform stats: total stores, active stores, pending verification, total sales volume
- `views/admin/stores/index.php` — table: store name, owner, email, status badge, registered date, actions (View / Activate / Suspend)
- `views/admin/stores/detail.php` — store profile + "Browse Store (View Only)" button
- Impersonation banner: when browsing as a store, a persistent top banner shows "Viewing as [Store Name] — Read Only" with "Exit" button

#### Routes (under `/admin/`)
```
GET  /admin/login          → login form
POST /admin/login          → authenticate
POST /admin/logout         → destroy admin session
GET  /admin/dashboard      → platform stats
GET  /admin/stores         → all stores list
GET  /admin/stores/:id     → store detail
POST /admin/stores/:id/activate  → set status = active
POST /admin/stores/:id/suspend   → set status = suspended
POST /admin/stores/:id/impersonate → set impersonation session
POST /admin/exit-impersonate      → clear impersonation
```

### Exit Criteria
- Admin logs in → sees platform stats (not a store dashboard)
- Click "Browse Store" on Store A → impersonation banner appears; navigating to `/inventory` shows Store A's inventory
- In impersonation mode: "Add Product" button is hidden; direct POST to `/inventory` returns 403
- Suspend store → store owner login attempt → error "Account suspended"

---

## Phase 12 — Receipts & PDF Invoices

**Goal:** Thermal-friendly receipt page and branded PDF invoice available for every sale.

### Deliverables

#### Thermal Receipt
- `views/sales/receipt.php` — print-optimised layout:
  - `@media print` CSS: hide all nav, sidebar, header; set width to 80mm; remove box shadows; use black on white only
  - Content: store name (large), address, date/time, sale number, divider, line items table (name, qty, price, total), divider, subtotal, GST, grand total (bold), payment method, customer name (if not walk-in), "Thank you" footer
  - `window.print()` called on `DOMContentLoaded`
  - Re-print button on `views/sales/detail.php`

#### PDF Invoice
- `PdfInvoiceService.php` — uses mPDF/TCPDF:
  - Header: store logo (if uploaded), store name, address
  - Invoice metadata: invoice number = sale_number, invoice date, customer name + mobile
  - Line items table with columns: #, Product, SKU, Qty, Unit Price, Line Total
  - Footer: subtotal, GST, grand total, payment method
  - Page footer: store name + "Generated by Kinara Store Hub"
- Route `GET /sales/:id/invoice.pdf` streams PDF with `Content-Disposition: attachment`

### Install
```bash
composer require mpdf/mpdf
# or
composer require tecnickcom/tcpdf
```

### Exit Criteria
- Open receipt page → browser print dialog appears automatically
- Printed receipt fits within 80mm width (test in Chrome print preview → paper size = 80mm custom)
- PDF invoice downloaded with store logo, all line items, and totals
- Sale with no customer → invoice shows "Walk-in Customer" or omits customer section

---

## Phase 13 — Android App

**Goal:** Native Android app (Kotlin + Jetpack) with login, inventory view, POS sales, and dashboard.

### Deliverables

#### Screens (MVP)
1. **Login** — email + password; stores JWT in EncryptedSharedPreferences
2. **Dashboard** — KPI cards + MPAndroidChart line chart (sales trend)
3. **Inventory List** — RecyclerView with stock badge; pull-to-refresh
4. **Product Detail** — view product info + variants
5. **POS Sale** — product search, cart, payment method, submit
6. **Sales History** — paginated list with date filter
7. **Customer List** — list with outstanding balance

#### Key Technical
- Retrofit 2 + OkHttp interceptor for JWT attachment
- OkHttp Authenticator for transparent token refresh on 401
- ViewModel + StateFlow for UI state
- Hilt for dependency injection
- `BuildConfig.API_BASE_URL` = `http://10.0.2.2/kinarahub/api/v1/` for emulator

### Exit Criteria
- Login → dashboard loads with real data from local API
- Add POS sale on Android → sale appears in web app immediately
- Force token expiry → app transparently refreshes and retries without re-login prompt
- No crash on network timeout or 500 error — error state shown in UI

---

## Phase 14 — iOS App

**Goal:** Native iOS app (Swift + SwiftUI) with the same feature scope as the Android app.

### Deliverables

#### Screens (MVP)
1. **Login** — email + password; JWT stored in Keychain
2. **Dashboard** — KPI cards + Swift Charts line chart
3. **Inventory List** — `List` with stock color badges; `.refreshable`
4. **Product Detail** — product info + variants
5. **POS Sale** — searchable product list, cart, payment method, submit
6. **Sales History** — paginated with date filter
7. **Customer List** — with outstanding balance highlight

#### Key Technical
- URLSession with `async/await`
- `@MainActor` ViewModels
- `KeychainAccess` package for token storage
- `NavigationStack` with `enum Route: Hashable` for type-safe navigation
- API base URL from `.xcconfig` per scheme

### Exit Criteria
- Same as Android Phase 13: login, real data, sale creation reflects on web, transparent token refresh

---

## Phase 15 — Polish, Security & Hardening

**Goal:** Production-readiness — security audit, performance, UX refinements, and cross-browser testing.

### Security Audit Checklist
- [ ] Run all POST endpoints without CSRF token → 403 returned
- [ ] Attempt SQL injection on all search/filter inputs → no DB error, no unexpected results
- [ ] Try to access another store's data by modifying IDs in URL/body → 403 or 404
- [ ] Upload PHP file as logo → rejected (MIME check working)
- [ ] Upload CSV with `=cmd|` formula injection → stored as plain text, no execution
- [ ] Verify JWT secret is not in any committed file
- [ ] Confirm `display_errors = Off` and `log_errors = On` in production php.ini

### Performance Checklist
- [ ] Run `EXPLAIN` on all dashboard queries → all use indexes
- [ ] Dashboard page load < 2 seconds with 1000 products and 500 sales in DB
- [ ] CSV export of 1000 products completes without memory exhaustion (unbuffered query)
- [ ] PDF generation for P&L report with 6 months of data < 5 seconds

### UX Refinements
- [ ] All toast notifications tested: success (green), error (red), undo (amber)
- [ ] Keyboard shortcuts tested: Esc, Enter, Ctrl+N on all applicable pages
- [ ] Dark mode tested on all 15+ pages — no white flash on theme toggle
- [ ] All modals: focus trap working; first field auto-focused on open
- [ ] Undo delete: 5-second window confirmed; undo click confirmed to cancel deletion
- [ ] All forms: SKU uppercase normalization, whitespace trimming on submit

### Cross-Browser Testing
- [ ] Chrome 100+ — full functionality
- [ ] Firefox 100+ — full functionality
- [ ] Edge 100+ — full functionality
- [ ] Safari 15+ — full functionality (check `gap` in flexbox, `has()` selector)
- [ ] iPad (1024px) — layout correct, no horizontal overflow
- [ ] Receipt print preview — 80mm width correct in all browsers

---

## Milestone Summary

| Milestone | Phases Complete | What's Usable |
|---|---|---|
| **M1 — Core Backend Ready** | 1–4 | Register store, log in, manage staff and roles |
| **M2 — Inventory Live** | 5 | Full inventory management with alerts |
| **M3 — Sales Live** | 6–7 | POS + bookkeeping, credit tracking |
| **M4 — Insights Live** | 8–9 | Dashboard + all 5 reports + exports |
| **M5 — API Ready** | 10 | Mobile apps can integrate |
| **M6 — Platform Admin** | 11 | Kinara Hub admin can manage all stores |
| **M7 — Full Web App** | 12 | Receipts + PDF invoices complete |
| **M8 — Android Beta** | 13 | Android app functional with real API |
| **M9 — iOS Beta** | 14 | iOS app functional with real API |
| **M10 — Production** | 15 | Security hardened, performance verified, cross-browser tested |
