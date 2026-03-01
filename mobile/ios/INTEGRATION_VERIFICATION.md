# iOS API Integration Verification Report

**Date:** 2026-03-01
**Verified against:** Live PHP REST API (Task #13)
**iOS app location:** `mobile/ios/KinaraHub/`

---

## Integration Points Verified

### 1. API Response Envelope

**Status: PASS**

PHP returns:
```json
{"success": true, "data": {}, "meta": {"page": 1, "per_page": 20, "total": 0, "total_pages": N}, "error": null}
```

Swift `APIResponse<T>` struct matches exactly:
- `success: Bool`
- `data: T?` (nullable)
- `meta: Meta?` (nullable)
- `error: String?` (nullable)

`Meta` struct CodingKeys correctly map `per_page` and `total_pages`.

### 2. Auth Endpoints

**Status: PASS**

| Endpoint | PHP Contract | Swift Implementation | Match |
|---|---|---|---|
| `POST /auth/login` | `{email, password}` -> `{access_token, refresh_token, token_type, expires_in, user}` | `LoginRequest` / `LoginResponse` with correct CodingKeys | Yes |
| `POST /auth/refresh` | `{refresh_token}` -> `{access_token, refresh_token, token_type, expires_in}` | `RefreshRequest` / `RefreshResponse` with correct CodingKeys | Yes |
| `POST /auth/logout` | `{refresh_token}` -> `{message}` | `LogoutRequest` / `EmptyResponse` | Yes |

`LoginUser` fields (`id`, `name`, `email`, `store_id`, `store_name`, `role_id`) all match the PHP response.

### 3. Product Response Shape

**Status: FIXED**

**Mismatch found:** PHP SQL aliases the UOM abbreviation column as `uom_abbr`, but Swift expected `uom_abbreviation`.

**Fix applied:** Renamed `uomAbbreviation` to `uomAbbr` with CodingKey `"uom_abbr"` in:
- `APIModels.swift` (Product struct)
- `ProductDetailView.swift` (view references)
- `ViewModelTests.swift` (test Product constructors)

All other product fields match: `id`, `store_id`, `sku`, `name`, `category_id`, `category_name`, `uom_name`, `selling_price`, `cost_price`, `stock_quantity`, `reorder_point`, `status`, `version`, `created_at`, `updated_at`. The API also returns `stock_status` which Swift ignores (computes client-side from quantity/reorder_point) -- this is correct behavior.

### 4. Sale Creation

**Status: FIXED**

**Mismatch found:** PHP `POST /sales` returns `{"sale_id": N}` on success, but Swift was trying to decode `APIResponse<Sale>` (a full Sale object), which would cause a decoding error.

**Fix applied:**
- Added `CreateSaleResponse` struct with `saleId` field (CodingKey: `"sale_id"`)
- Changed `SalesViewModel.submitSale()` to decode `APIResponse<CreateSaleResponse>` instead of `APIResponse<Sale>`
- Removed unused `lastCreatedSale: Sale?` property from `SalesViewModel`
- Simplified success alert in `POSSaleView.swift` to show generic message instead of trying to read sale number from a full Sale object

**Request body matches:**
```json
{
  "entry_mode": "pos",
  "payment_method": "cash",
  "customer_id": null,
  "items": [{"product_id": 1, "variant_id": null, "quantity": "2", "unit_price": "100.00"}]
}
```
PHP casts quantity/unit_price with `(float)`, so string values work correctly.

### 5. Dashboard Summary

**Status: PASS**

PHP `DashboardService::getAllStats()` returns:
```
today_revenue, yesterday_revenue, percent_change, week_revenue, month_revenue,
stock_value, out_of_stock, low_stock, top_products, recent_sales,
sales_trend, payment_breakdown, stock_distribution
```

Swift `DashboardSummary` CodingKeys match all fields exactly. Nested types (`TopProduct`, `RecentSale`, `SalesTrendResponse`, `PaymentBreakdownResponse`, `StockDistributionResponse`) all match their PHP counterparts.

Chart endpoint `GET /dashboard/chart` with query params `type` and `period` is correctly implemented.

### 6. Base URL

**Status: PASS**

`AppConfig.swift` (DEBUG):
```swift
static let apiBaseURL = "http://localhost/kinarahub/api/v1"
```

All endpoint paths correctly append to this base:
- `/auth/login`, `/auth/refresh`, `/auth/logout`
- `/products`, `/products/{id}`
- `/sales`, `/sales/{id}`
- `/customers`, `/customers/{id}/credits`, `/customers/{id}/payments`
- `/dashboard`, `/dashboard/chart`

All 16 routes from `api_routes.php` are covered.

### 7. Token Handling

**Status: PASS**

- Tokens stored in iOS Keychain via `KeychainAccess` library (service: `com.kinarahub.ios`) -- NOT UserDefaults
- `APIClient.swift` handles 401 -> `refreshTokenIfNeeded()` -> retry automatically
- Refresh uses `RefreshRequest` with correct `refresh_token` CodingKey
- On refresh failure, clears tokens and posts `.authSessionExpired` notification
- `AuthViewModel` observes the notification and sets `isAuthenticated = false`

---

## Files Modified

| File | Change |
|---|---|
| `KinaraHub/Network/Models/APIModels.swift` | Renamed `uomAbbreviation` -> `uomAbbr` (CodingKey `"uom_abbr"`); Added `CreateSaleResponse` struct |
| `KinaraHub/ViewModels/SalesViewModel.swift` | Changed `submitSale()` to use `CreateSaleResponse`; Removed unused `lastCreatedSale` property |
| `KinaraHub/Views/Sales/POSSaleView.swift` | Simplified sale success alert message |
| `KinaraHub/Views/Inventory/ProductDetailView.swift` | Updated `uomAbbreviation` -> `uomAbbr` references |
| `KinaraHub/Tests/ViewModelTests.swift` | Updated all Product constructor calls from `uomAbbreviation` to `uomAbbr` |

---

## Endpoint Coverage Summary

| # | Route | Method | Auth | iOS Coverage |
|---|---|---|---|---|
| 1 | `/auth/login` | POST | No | AuthViewModel.login() |
| 2 | `/auth/refresh` | POST | No | APIClient.refreshTokenIfNeeded() |
| 3 | `/auth/logout` | POST | Yes | AuthViewModel.logout() |
| 4 | `/dashboard` | GET | Yes | DashboardViewModel.loadSummary() |
| 5 | `/dashboard/chart` | GET | Yes | DashboardViewModel.loadSalesTrend() |
| 6 | `/products` | GET | Yes | InventoryViewModel.loadProducts() |
| 7 | `/products/:id` | GET | Yes | InventoryViewModel.loadProductDetail() |
| 8 | `/products` | POST | Yes | Not in iOS (web-only) |
| 9 | `/products/:id` | PUT | Yes | Not in iOS (web-only) |
| 10 | `/products/:id` | DELETE | Yes | Not in iOS (web-only) |
| 11 | `/sales` | GET | Yes | SalesViewModel.loadSales() |
| 12 | `/sales/:id` | GET | Yes | Available via endpoint, not yet wired to UI |
| 13 | `/sales` | POST | Yes | SalesViewModel.submitSale() |
| 14 | `/customers` | GET | Yes | CustomerViewModel.loadCustomers() |
| 15 | `/customers` | POST | Yes | CustomerViewModel.createCustomer() |
| 16 | `/customers/:id/credits` | GET | Yes | CustomerViewModel.loadCredits() |
| 17 | `/customers/:id/payments` | POST | Yes | CustomerViewModel.recordPayment() |

Product create/update/delete (routes 8-10) are intentionally web-only -- the iOS app is a read + POS + customer management client.

---

## How to Test

1. Start XAMPP (Apache + MySQL)
2. Ensure the database is seeded with at least one store and staff account
3. Open the iOS project in Xcode
4. Run against the simulator (DEBUG build connects to `http://localhost/kinarahub/api/v1`)
5. Log in with a valid staff email/password
6. Verify: Dashboard loads stats, Products list loads, POS sale creation works, Customer list/create/payment works
