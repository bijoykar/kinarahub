# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Project

**Kinara Store Hub** — a multi-tenant SaaS platform for inventory and sales management. Multiple independent Kinara stores share a single deployment. Full requirements are in `spec.md`.

---

## Stack

| Layer | Choice | Notes |
|---|---|---|
| Backend | PHP (no framework) | Structured as MVC by convention |
| Database | MySQL via XAMPP | PDO only — no raw string interpolation ever |
| Frontend | PHP-rendered HTML + Tailwind CSS (CDN) + Vanilla JS | No build pipeline, no JS framework |
| Charts | Chart.js via CDN | |
| PDF generation | mPDF or TCPDF | For invoices and report exports |
| API auth | JWT (HS256) | Access token 15 min, refresh token 30 days |
| Web auth | PHP sessions | `session_regenerate_id(true)` on login |

---

## Local Development (XAMPP)

- Document root: `C:\xampp\htdocs\kinarahub\`
- App runs at: `http://localhost/kinarahub/`
- Admin panel: `http://localhost/kinarahub/admin/`
- API base: `http://localhost/kinarahub/api/v1/`
- Start/stop: XAMPP Control Panel → Apache + MySQL
- DB management: `http://localhost/phpmyadmin/`
- PHP errors: enable in `C:\xampp\php\php.ini` → `display_errors = On`, `error_reporting = E_ALL`

---

## Planned Directory Structure

```
kinarahub/
├── public/               # Entry points only (index.php, admin/index.php)
├── app/
│   ├── Controllers/      # Thin controllers — validate input, call services, return response
│   ├── Models/           # DB queries via PDO; one class per table group
│   ├── Services/         # Business logic (SaleService, InventoryService, etc.)
│   ├── Middleware/        # Auth check, CSRF, role/permission enforcement
│   └── Helpers/          # TenantScope, JWT, response formatter, pagination
├── api/
│   └── v1/               # API entry point and route definitions
├── config/
│   ├── db.php            # PDO connection; reads db_connection from stores table for isolated DBs
│   └── app.php           # Constants: timezone (Asia/Kolkata), currency (INR), pagination defaults
├── views/                # PHP templates; layouts/partials separated
├── admin/                # Platform admin panel (separate auth, separate controllers)
├── uploads/              # Outside webroot ideally; logo and CSV files stored here
└── .env                  # JWT secret, DB credentials — never committed
```

---

## Architecture Rules

### Multi-tenancy
Every tenant-scoped query MUST go through `TenantScope` — never write bare `WHERE store_id = ?` inline across controllers. This allows the future per-store isolated-DB migration to be handled in one place (`config/db.php` reads `stores.db_connection`).

### Request flow
```
public/index.php → Router → Middleware (auth → CSRF → permission) → Controller → Service → Model → PDO
```

### API vs Web
- Web: PHP sessions, CSRF on every POST, server-rendered views.
- API (`/api/v1/`): stateless JWT, `Authorization: Bearer` header, JSON responses only, no HTML.
- `store_id` is NEVER accepted from a request body in either path — always sourced from `$_SESSION['store_id']` or the decoded JWT payload.

### RBAC
Permissions are stored in `role_permissions` (module + action + allowed) and `role_field_restrictions` (field_key + hidden). Enforcement is always server-side. The modules are: `inventory`, `sales`, `customers`, `reports`, `settings`. Sensitive field keys that can be hidden: `cost_price`, `profit_margin`, `store_financials`.

### Sales stock decrement
Stock is decremented atomically inside a transaction when a sale is committed. Use optimistic locking (`version` field on `products` and `product_variants`) — return HTTP 409 if version mismatch on UPDATE.

### API response envelope
All `/api/v1/` responses use this shape — never deviate:
```json
{ "success": true, "data": {}, "meta": { "page": 1, "per_page": 20, "total": 0 }, "error": null }
```

---

## Database Conventions

- Every table: `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`, `updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`.
- Every tenant table: `store_id INT NOT NULL` (indexed).
- Optimistic locking tables (products, product_variants, sales): `version INT DEFAULT 0`.
- SKU: unique per store (not globally unique). Normalized to uppercase on write.
- Fractional quantities: `DECIMAL(10,3)` for stock, `DECIMAL(10,2)` for prices.
- Sale numbers: auto-formatted `INV-00001` per store (not global sequence).

---

## Key Business Logic

- **Stock status**: `qty == 0` → Out of Stock (red); `0 < qty <= reorder_point` → Low Stock (amber); `qty > reorder_point` → In Stock (green). Computed on read, not stored.
- **CSV import**: Upsert on SKU — update all fields if SKU exists, insert if new. Show summary after: X inserted, Y updated, Z failed.
- **Anonymous customer**: Each store has a seeded `Walk-in Customer` (`is_default = 1`) used when no customer is provided. Credit sales require a named customer.
- **Receipts**: `/sales/{id}/receipt` — 80mm-width CSS print page. `/sales/{id}/invoice.pdf` — branded PDF via mPDF/TCPDF.
- **JWT rotation**: Refresh tokens are rotated on every use and stored in `refresh_tokens` table.

---

## Skills

| Skill | Invoke | Purpose |
|---|---|---|
| `/frontend-design` | `/frontend-design` | Build distinctive, production-grade UI components and pages |
| `/backend-agent` | `/backend-agent` | Server-side logic, APIs, DB schema, security-focused implementation |

## PROMPT
note all promt in promt.md file after easch promt given 

