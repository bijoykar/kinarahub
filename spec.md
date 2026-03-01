# Kinara Store Hub — Full System Specification

> Version: 1.0 | Date: 2026-03-01 | Status: Approved

---

## 1. Overview

**Kinara Store Hub** is a multi-tenant SaaS platform where multiple independent Kinara stores can register and manage their inventory and sales from a shared web application.

| Layer | Technology |
|---|---|
| Backend | PHP (no framework — structured MVC by convention) |
| Database | MySQL |
| Frontend | PHP-rendered HTML + Tailwind CSS + Vanilla JavaScript |
| API | RESTful JSON API, versioned at `/api/v1/` |
| Auth (Web) | PHP sessions |
| Auth (API) | JWT — access token + refresh token |
| Currency | INR (₹), hardcoded platform-wide |
| Timezone | IST (Asia/Kolkata), hardcoded platform-wide |

---

## 2. Multi-Tenancy Architecture

### Current: Shared Database, Tenant-Scoped Tables
All stores share the same MySQL database. Every table that holds store-specific data includes a `store_id` column (foreign key to `stores`). All queries MUST include `WHERE store_id = ?` — never omit this.

### Future: Per-Store Isolated Database
The platform owner (Kinara Hub admin) can manually migrate a specific store to its own dedicated database. When triggered:
- All historical data for that store is migrated to the new database.
- The `stores` table in the shared DB gains a `db_connection` JSON column to store connection credentials for isolated-DB stores.
- A database resolver layer reads this column and switches the PDO connection at request time.
- No self-service upgrade by store owners. Admin-only operation.

**Design rule:** Never write raw `store_id` filters inline — always use a `TenantScope` helper/middleware that injects the filter, so it can be swapped for a DB switch later.

---

## 3. Store Registration & Onboarding

### Registration Flow
1. Owner fills registration form: store name, owner name, email, mobile, password.
2. System sends a verification email with a signed token link.
3. Owner clicks link → account activates → redirected to store setup (upload logo, enter address).
4. Store status: `pending_verification` → `active`.

### Store Profile Fields
| Field | Required |
|---|---|
| Store name | Yes |
| Owner name | Yes |
| Email | Yes (unique) |
| Mobile | Yes |
| Password (hashed bcrypt) | Yes |
| Address (street, city, state, pincode) | Optional (set after registration) |
| Logo (image upload) | Optional |

### Store Statuses
- `pending_verification` — email not yet verified
- `active` — normal operation
- `suspended` — disabled by platform admin

---

## 4. Authentication & Sessions

### Web (PHP Sessions)
- Login via email + password.
- `$_SESSION['store_id']`, `$_SESSION['user_id']`, and `$_SESSION['role_id']` set on login.
- Session lifetime: 8 hours idle timeout.
- CSRF token on all POST forms.

### API (JWT)
- `POST /api/v1/auth/login` → returns `{ access_token, refresh_token, expires_in }`.
- Access token lifetime: 15 minutes.
- Refresh token lifetime: 30 days, stored in `refresh_tokens` table (rotated on use).
- `POST /api/v1/auth/refresh` → returns new access token.
- `POST /api/v1/auth/logout` → invalidates refresh token.
- All API requests send `Authorization: Bearer <token>` header.

---

## 5. RBAC — Roles & Permissions

### Model
Fully configurable per store. Owner defines named roles and assigns permissions.

**Modules:**
- `inventory` — product CRUD, CSV import/export
- `sales` — POS, bookkeeping entry, sales history
- `customers` — customer list, credit/dues management
- `reports` — view and export all reports
- `settings` — store profile, roles, staff management

**Actions per module:** `create`, `read`, `update`, `delete`

**Field-level restrictions:**
- Sensitive fields that can be hidden per role: `cost_price`, `profit_margin`, `store_financials`
- Owner sets which roles cannot see these fields; backend enforces and omits them from responses.

### Tables
```sql
roles (id, store_id, name, description)
role_permissions (id, role_id, module, action, allowed TINYINT)
role_field_restrictions (id, role_id, field_key, hidden TINYINT)
staff (id, store_id, name, email, mobile, password_hash, role_id, status, created_at, updated_at)
```

### Default Role: Owner
Auto-created on store registration. Full access to all modules and all fields. Cannot be deleted.

---

## 6. Inventory Management

### Product Fields
| Field | Notes |
|---|---|
| `id` | Auto PK |
| `store_id` | Tenant scope |
| `sku` | Unique per store, normalized to uppercase |
| `name` | |
| `category_id` | FK to `categories` |
| `uom_id` | FK to `units_of_measure` (e.g., Pieces, Kg, Litre, Box) |
| `selling_price` | DECIMAL(10,2) |
| `cost_price` | DECIMAL(10,2) — used for P&L reports |
| `stock_quantity` | DECIMAL(10,3) — supports fractional (e.g., 0.5 kg) |
| `reorder_point` | DECIMAL(10,3) — per-product low-stock threshold |
| `status` | `active` / `inactive` |
| `created_at`, `updated_at` | Audit fields |
| `version` | INT — optimistic locking |

### Product Variants
A product can have multiple variants. Each variant is independent with its own SKU, selling price, cost price, stock quantity, and reorder point.

```sql
product_variants (
  id, product_id, store_id,
  variant_name,        -- e.g. "Red / Large", "500ml", "10mg"
  sku,                 -- unique per store
  selling_price, cost_price,
  stock_quantity, reorder_point,
  created_at, updated_at, version
)
```

### Categories & UOM
- `categories (id, store_id, name)` — owner-defined, scoped per store.
- `units_of_measure (id, name, abbreviation)` — platform-wide list: Pieces, Kg, Gram, Litre, ML, Box, Pack, Dozen.

### Stock Alert Logic
- On every inventory update, compare `stock_quantity` to `reorder_point`.
- Status:
  - `stock_quantity == 0` → **Out of Stock** (red badge)
  - `0 < stock_quantity <= reorder_point` → **Low Stock** (amber badge)
  - `stock_quantity > reorder_point` → **In Stock** (green badge)
- Alerts displayed in-app: dashboard widget + inline badge on inventory table rows.

### Bulk CSV Import
- Owner uploads a CSV file.
- Expected columns: `sku, name, category, uom, selling_price, cost_price, stock_quantity, reorder_point`
- **Conflict resolution: Upsert** — if SKU exists, update all fields; if new, insert.
- After processing: show summary — X inserted, Y updated, Z failed (with row numbers and reasons).
- Validate before upsert: required fields present, numeric fields are valid numbers, price ≥ 0.

### CSV Export
Download current inventory as CSV (all fields, current stock levels).

---

## 7. Sales Management

### Two Entry Modes

#### Mode 1: Quick POS (real-time cart)
- Search/add products to cart by name or SKU.
- Adjust quantity per line item.
- No discounts.
- Select payment method: Cash | UPI | Card | Credit.
- If Credit: must select or create a named customer.
- Submit → sale recorded → stock decremented → receipt/invoice screen shown.

#### Mode 2: Bookkeeping Entry (manual)
- Enter sale line items manually (product, qty, price).
- Select date (can backdate).
- Select payment method.
- Customer optional (defaults to anonymous if not Credit).

### Sale Record Structure
```sql
sales (
  id, store_id,
  sale_number,           -- auto-formatted e.g. INV-00001
  sale_date,
  entry_mode,            -- 'pos' | 'booking'
  customer_id,           -- nullable FK
  payment_method,        -- 'cash' | 'upi' | 'card' | 'credit'
  subtotal, tax_amount, total_amount,
  notes,
  created_by,            -- staff_id FK
  created_at, updated_at
)

sale_items (
  id, sale_id, store_id,
  product_id, variant_id,          -- variant_id nullable
  product_name_snapshot, sku_snapshot,
  quantity, unit_price, cost_price_snapshot, line_total
)
```

### Anonymous Customer
- A default customer record (`name: "Walk-in Customer"`, `is_default: 1`) is auto-created for each store on registration.
- Used whenever customer info is not provided.

---

## 8. Customer & Credit Management

### Customer Fields
```sql
customers (
  id, store_id,
  name, mobile, email,         -- email nullable
  is_default TINYINT,
  outstanding_balance DECIMAL(10,2) DEFAULT 0.00,
  created_at, updated_at
)
```

### Credit Sales
When payment method = `credit`, a credit entry is created:
```sql
customer_credits (
  id, store_id, customer_id, sale_id,
  amount_due, amount_paid, balance,
  due_date,                    -- nullable
  created_at, updated_at
)
```

### Payments Against Dues
- Owner records a payment against a customer's outstanding balance.
- Partial payments supported: amount paid reduces `outstanding_balance` and updates `customer_credits.amount_paid` and `balance`.
```sql
credit_payments (
  id, store_id, customer_id, credit_id,   -- credit_id nullable
  amount_paid, payment_method, payment_date, notes,
  created_at
)
```

---

## 9. Dashboard

### Layout
- Sidebar navigation + top header (store name, staff name, dark mode toggle).
- Responsive (works on tablet and desktop; mobile-friendly but not mobile-first).

### KPI Widgets
| Widget | Description |
|---|---|
| Sales Today | Total revenue today vs yesterday (% change) |
| Sales This Week | Weekly total |
| Sales This Month | Monthly total |
| Total Stock Value | Sum of (cost_price × stock_quantity) across all products |
| Out of Stock Count | Clickable — goes to filtered inventory list |
| Low Stock Count | Clickable — goes to filtered inventory list |
| Top 5 Products Today | Mini table: product name, units sold, revenue |
| Recent Sales | Last 10 transactions |

### Charts (Chart.js via CDN)
- **Sales Trend** — line chart, toggle: day / week / month / year.
- **Sales by Payment Method** — donut chart for selected period.
- **Stock Status Distribution** — donut: In Stock / Low Stock / Out of Stock counts.

---

## 10. Reports

All reports filterable by date range. All exportable as PDF. Field-level permissions enforced (cost_price hidden for restricted roles).

| Report | Description |
|---|---|
| **Sales by Product (Top Sellers)** | Products ranked by qty sold and revenue. Shows cost, revenue, gross profit per product. |
| **Inventory Aging (Slow Movers)** | Products with zero sales in last 30 / 60 / 90 days. Shows stock qty and value tied up. |
| **Profit & Loss Summary** | Revenue, COGS, gross profit, gross margin % for period. Breakdown by category. |
| **Customer Credit / Dues** | Customers with outstanding balance. Columns: name, mobile, credit total, paid, balance due. |
| **GST Tax Summary** | Total taxable sales, total GST collected for a date range. Exportable as CSV/PDF for accountant. |

### Data Exports
| Export | Format |
|---|---|
| Sales transactions (date range) | CSV |
| All reports | PDF |
| Customer dues list | CSV |
| GST tax summary | CSV + PDF |
| Inventory snapshot | CSV |

---

## 11. API Module

### Base URL
`/api/v1/`

### Authentication
All endpoints except `/auth/*` require: `Authorization: Bearer <access_token>`

### Endpoints

**Auth**
```
POST /api/v1/auth/login
POST /api/v1/auth/refresh
POST /api/v1/auth/logout
```

**Inventory**
```
GET    /api/v1/products              — paginated, filterable by category/status
GET    /api/v1/products/{id}
POST   /api/v1/products
PUT    /api/v1/products/{id}
DELETE /api/v1/products/{id}
GET    /api/v1/products/{id}/variants
```

**Sales**
```
POST /api/v1/sales
GET  /api/v1/sales                   — paginated, filterable by date
GET  /api/v1/sales/{id}
```

**Customers**
```
GET  /api/v1/customers
POST /api/v1/customers
GET  /api/v1/customers/{id}/credits
POST /api/v1/customers/{id}/payments
```

**Dashboard**
```
GET /api/v1/dashboard/summary        — key stats for mobile home screen
```

### Standard Response Envelope
```json
{
  "success": true,
  "data": { },
  "meta": { "page": 1, "per_page": 20, "total": 150 },
  "error": null
}
```

---

## 12. Platform Admin Panel

Accessible at `/admin/` with a separate admin login. Admin credentials seeded via a setup script.

### Features
- **Stores list** — all registered stores, status badges, registration date, owner email.
- **Store detail** — view store profile and owner info.
- **Store actions** — Activate / Suspend any store.
- **Impersonation (view-only)** — Admin can browse a store's inventory, sales, and reports in read-only mode. Cannot create, edit, or delete anything.
- **Platform stats** — Total stores, total active stores, total sales volume across all stores.
- **Pending registrations** — Stores awaiting email verification shown separately.

---

## 13. Receipts & Invoices

### Thermal Receipt (printable)
- Print-optimised page at `/sales/{id}/receipt` — 80mm-width CSS, no decorative styling.
- Content: store name, date/time, sale number, line items (name, qty, unit price, line total), subtotal, GST, grand total, payment method.
- `window.print()` triggered on page load; re-printable from sale detail page.

### PDF Invoice
- Generated server-side via **mPDF** or **TCPDF**.
- Branded with store logo and address.
- Same fields as receipt plus customer name (if provided).
- Download endpoint: `GET /sales/{id}/invoice.pdf`

---

## 14. Database Schema — Key Tables

```
stores
staff
roles
role_permissions
role_field_restrictions
refresh_tokens

categories
units_of_measure
products
product_variants

sales
sale_items

customers
customer_credits
credit_payments
```

**All tables include:**
```sql
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

**All tenant-scoped tables include:**
```sql
store_id INT NOT NULL
```

---

## 15. UX & Frontend Conventions

| Convention | Detail |
|---|---|
| CSS Framework | Tailwind CSS via CDN (no build pipeline) |
| Dark Mode | Tailwind `dark:` classes; toggled via `<html class="dark">`; preference in `localStorage` |
| Charts | Chart.js via CDN |
| Toast Notifications | Custom vanilla JS toast (top-right) for all CRUD success/error feedback |
| Modals | Vanilla JS modal system for add/edit forms |
| Keyboard Shortcuts | Esc closes modal, Enter submits focused form, Ctrl+N opens new item form |
| Form UX | Auto-focus first field on modal open; SKU auto-uppercased on input |
| Pagination | All list views paginated (default 20 per page) |
| Undo Delete | 5-second "Undo" toast after delete before DB removal |
| Optimistic Locking | `version` field checked on all UPDATE queries — 409 returned if stale |

---

## 16. Security Checklist

- All DB queries use PDO prepared statements — no string interpolation
- Passwords hashed with `password_hash($pass, PASSWORD_BCRYPT)`
- CSRF token on every POST form (validated server-side)
- File uploads (logo, CSV): MIME type validated, size limited, stored outside webroot with randomised filenames
- JWT signed with HS256; secret stored in `.env` (never committed)
- `store_id` is NEVER taken from request body — always sourced from authenticated session/token
- Field-level permissions enforced server-side; frontend hiding is cosmetic only

---

## 17. Carry-Forward Improvements

1. **Pagination** — Offset-based pagination on all list views and API endpoints
2. **Audit Fields** — `created_at` / `updated_at` on every table
3. **Optimistic Locking** — `version` INT on products, variants, sales records
4. **Toast Notifications** — All CRUD actions give immediate feedback
5. **Keyboard Shortcuts** — Esc, Enter, Ctrl+N
6. **Inventory CSV Export** — One-click download of full inventory snapshot
7. **Dark Mode** — Tailwind dark mode toggle, preference persisted in localStorage
8. **Form Auto-focus** — First field focused when any modal opens
9. **Undo Delete** — 5-second grace window before permanent DB deletion
10. **Input Sanitization** — Whitespace trimmed, SKU normalized to uppercase on backend
