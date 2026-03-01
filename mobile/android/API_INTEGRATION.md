# Kinara Hub Android — API Integration Guide

## Base URL Configuration

### Emulator (default)
```
API_BASE_URL = "http://10.0.2.2/kinarahub/api/v1/"
```
`10.0.2.2` is the Android emulator's alias for the host machine's `localhost`. This is set in `app/build.gradle.kts` under `defaultConfig`.

### Physical Device
When testing on a physical device connected to the same network as the dev machine:
```
API_BASE_URL = "http://192.168.x.x/kinarahub/api/v1/"
```
Replace with the host machine's LAN IP. Ensure Apache is listening on `0.0.0.0` (not `127.0.0.1` only).

### Production
Set in the `release` build type in `app/build.gradle.kts`:
```kotlin
buildTypes {
    release {
        buildConfigField("String", "API_BASE_URL", "\"https://your-domain.com/api/v1/\"")
    }
}
```
Access at runtime via `BuildConfig.API_BASE_URL`. No code changes needed -- Retrofit reads this value through the DI module (`NetworkModule.kt`).

### Switching Environments
To add a staging environment, create a new build flavor or build type:
```kotlin
buildTypes {
    create("staging") {
        initWith(getByName("debug"))
        buildConfigField("String", "API_BASE_URL", "\"https://staging.your-domain.com/api/v1/\"")
    }
}
```

---

## API Response Envelope

All endpoints return this standard shape:
```json
{
  "success": true,
  "data": { ... },
  "meta": { "page": 1, "per_page": 20, "total": 150 },
  "error": null
}
```

Kotlin model:
```kotlin
data class ApiResponse<T>(
    val success: Boolean,
    val data: T?,
    val meta: Meta?,
    val error: String?
)
```

---

## Retrofit Endpoints

### Auth

| Method | Path | Request Body | Response `data` Type | Notes |
|--------|------|-------------|---------------------|-------|
| POST | `auth/login` | `LoginRequest(email, password)` | `AuthData` | Returns access_token, refresh_token, user info |
| POST | `auth/refresh` | `RefreshRequest(refresh_token)` | `AuthData` | Rotates refresh token on each use |
| POST | `auth/logout` | (none, uses Bearer token) | `Unit` | Invalidates refresh token server-side |

### Products

| Method | Path | Request Body / Params | Response `data` Type | Notes |
|--------|------|----------------------|---------------------|-------|
| GET | `products` | `?page, per_page, search, category_id, status` | `List<Product>` | Paginated, filterable |
| GET | `products/{id}` | — | `Product` | Includes variants if present |
| POST | `products` | `CreateProductRequest` | `Product` | SKU auto-uppercased server-side |
| PUT | `products/{id}` | `CreateProductRequest` | `Product` | Requires `version` for optimistic lock |
| DELETE | `products/{id}` | — | `Unit` | |
| GET | `products/{id}/variants` | — | `List<ProductVariant>` | |

### Sales

| Method | Path | Request Body / Params | Response `data` Type | Notes |
|--------|------|----------------------|---------------------|-------|
| POST | `sales` | `CreateSaleRequest` | `Sale` | Atomic: insert sale + decrement stock |
| GET | `sales` | `?page, per_page, from, to` | `List<Sale>` | Date-filterable, paginated |
| GET | `sales/{id}` | — | `Sale` | Includes line items |

### Customers

| Method | Path | Request Body / Params | Response `data` Type | Notes |
|--------|------|----------------------|---------------------|-------|
| GET | `customers` | `?page, per_page, search` | `List<Customer>` | Walk-in Customer (is_default=1) included |
| POST | `customers` | `CreateCustomerRequest(name, mobile?, email?)` | `Customer` | |
| GET | `customers/{id}/credits` | — | `List<CustomerCredit>` | Credit history for one customer |
| POST | `customers/{id}/payments` | `RecordPaymentRequest(amount, payment_method, notes?)` | `Unit` | Partial payments supported |

### Dashboard

| Method | Path | Request Body / Params | Response `data` Type | Notes |
|--------|------|----------------------|---------------------|-------|
| GET | `dashboard/summary` | — | `DashboardSummary` | KPIs, top products, recent sales, sales trend |

---

## Token Refresh Flow

```
  Client                          Server
    |                               |
    |--- GET /api/v1/products ----->|
    |                               |
    |<--- 401 Unauthorized ---------|
    |                               |
    | [OkHttp Authenticator fires]  |
    |                               |
    |--- POST /api/v1/auth/refresh->|
    |    { refresh_token: "..." }   |
    |                               |
    |<--- 200 OK -------------------|
    |    { access_token, refresh_   |
    |      token (new) }            |
    |                               |
    | [Save new tokens to           |
    |  EncryptedSharedPreferences]  |
    |                               |
    |--- GET /api/v1/products ----->|
    |    Authorization: Bearer NEW  |
    |                               |
    |<--- 200 OK -------------------|
```

### Refresh Failure (expired refresh token)

```
  Client                          Server
    |                               |
    |--- GET /api/v1/products ----->|
    |<--- 401 Unauthorized ---------|
    |                               |
    |--- POST /auth/refresh ------->|
    |    { refresh_token: "..." }   |
    |                               |
    |<--- 401 Unauthorized ---------|
    |                               |
    | [Clear all tokens]            |
    | [Navigate to LoginScreen]     |
```

### Thread Safety

The `TokenRefreshAuthenticator` uses a `synchronized` block and a `@Volatile isRefreshing` flag to prevent multiple concurrent threads from all attempting to refresh at the same time. If Thread A is already refreshing, Thread B will see the updated token after Thread A finishes and retry with it.

### Implementation Files
- `AuthInterceptor.kt` -- attaches `Authorization: Bearer` header to all requests (except login/refresh)
- `TokenRefreshAuthenticator.kt` -- OkHttp `Authenticator` that intercepts 401 responses
- `TokenStore.kt` -- `EncryptedSharedPreferences` wrapper for secure token storage

---

## Edge Cases Handled

### HTTP 409 — Optimistic Lock Conflict
- **When:** Creating a sale that touches a product whose `version` has changed since it was last read
- **Cause:** Another user/session modified the product's stock between when the POS screen loaded and when the sale was submitted
- **User message:** "Stock conflict. A product was modified. Please refresh and try again."
- **Where:** `POSSaleViewModel.kt` in the `submitSale()` response handler

### HTTP 422 — Validation Errors
- **When:** POST/PUT with missing or invalid fields
- **User message:** "Invalid sale data. Please check your cart." / "Please check your input"
- **Where:** All ViewModels handle 422 in their submit/create functions

### HTTP 401 — Unauthorized
- **When:** Access token expired or invalid
- **Handling:** Automatic via `TokenRefreshAuthenticator` (see flow above)
- **If refresh also fails:** Tokens are cleared, user lands on LoginScreen
- **Note:** The authenticator adds `X-Retry-After-Refresh` header to prevent infinite retry loops

### HTTP 403 — Forbidden
- **When:** User's role does not have permission for the requested action
- **User message:** "Account suspended. Contact support." (login) or generic error

### Network Timeout
- **Configuration:** 30-second timeout for connect, read, and write (set in `NetworkModule.kt`)
- **User message:** "Network error. Please check your connection."
- **Handling:** All ViewModel `try/catch` blocks catch general `Exception` (which includes `SocketTimeoutException`, `UnknownHostException`, `IOException`)

### Empty States
- All list screens (Inventory, Sales, Customers) show an `EmptyState` composable when the API returns an empty list
- Dashboard shows individual empty states per section (no top products, no recent sales, no trend data)

### Pagination
- All list screens implement infinite scroll via `LazyListState` monitoring
- Loads next page when user scrolls within 3 items of the end
- Prevents duplicate loads with `isLoadingMore` guard flag

---

## File Map

```
data/
  remote/
    ApiService.kt                  -- Retrofit interface (all endpoints)
    AuthInterceptor.kt             -- OkHttp Interceptor (JWT header)
    TokenRefreshAuthenticator.kt   -- OkHttp Authenticator (401 refresh)
    models/
      ApiResponse.kt               -- Standard envelope + Meta
      AuthModels.kt                -- LoginRequest, RefreshRequest, AuthData, UserInfo
      ProductModels.kt             -- Product, ProductVariant, CreateProductRequest
      SaleModels.kt                -- Sale, SaleItem, CreateSaleRequest
      CustomerModels.kt            -- Customer, CustomerCredit, RecordPaymentRequest
      DashboardModels.kt           -- DashboardSummary, TopProduct, SalesTrend
  local/
    TokenStore.kt                  -- EncryptedSharedPreferences wrapper

di/
  NetworkModule.kt                 -- Hilt: OkHttpClient, Retrofit, ApiService providers

ui/
  components/
    CommonComponents.kt            -- StockBadge, PaymentMethodBadge, KpiCard, etc.
    SalesTrendChart.kt             -- MPAndroidChart LineChart composable
  screens/
    login/          LoginScreen.kt, LoginViewModel.kt
    dashboard/      DashboardScreen.kt, DashboardViewModel.kt
    inventory/      InventoryListScreen.kt, InventoryViewModel.kt,
                    ProductDetailScreen.kt, ProductDetailViewModel.kt
    sales/          POSSaleScreen.kt, POSSaleViewModel.kt,
                    SalesHistoryScreen.kt, SalesHistoryViewModel.kt
    customers/      CustomerListScreen.kt, CustomerViewModel.kt
  navigation/
    NavGraph.kt                    -- Route definitions
    KinaraNavHost.kt               -- NavHost with all composable destinations
  theme/
    Theme.kt                       -- Material 3 light/dark color schemes
```

---

## Security Notes

- Tokens stored in `EncryptedSharedPreferences` using AES-256-GCM encryption
- `AndroidManifest.xml` has `android:allowBackup="false"` to prevent token extraction via backup
- `store_id` is never sent in request bodies -- always derived from JWT payload server-side
- `usesCleartextTraffic="true"` is set for local dev (HTTP to 10.0.2.2). Remove for production or enforce HTTPS via network security config.
