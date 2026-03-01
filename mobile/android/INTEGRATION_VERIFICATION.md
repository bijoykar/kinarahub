# Android API Integration Verification Report

**Date:** 2026-03-01
**Verified Against:** PHP REST API v1 (Task #13)

---

## Summary

A thorough integration pass was performed comparing every Android Retrofit endpoint and data model against the live PHP API controllers and route definitions. **8 mismatches were found and fixed.**

---

## Integration Points Verified

### 1. API Response Envelope

**Status:** PASS (was already correct)

The PHP API returns:
```json
{"success": true, "data": {}, "meta": {"page": 1, "per_page": 20, "total": 0, "total_pages": 1}, "error": null}
```

`ApiResponse.kt` correctly wraps this with `ApiResponse<T>` generic, plus `Meta` data class with `@SerializedName` annotations for `per_page` and `total_pages`.

### 2. Auth Endpoints

**Status:** FIXED (3 issues)

| Endpoint | Issue | Fix |
|---|---|---|
| `POST /auth/login` | No issues | Request body `{email, password}` matches. Response `AuthData` includes `access_token`, `refresh_token`, `token_type`, `expires_in`, `user` -- all match PHP. |
| `POST /auth/refresh` | `AuthData.refreshToken` was non-nullable, but refresh endpoint always returns `refresh_token` | Made `refreshToken` nullable in `AuthData` for safety; updated `TokenStore.saveAuth()` to use `?.let` |
| `POST /auth/logout` | Was `suspend fun logout()` with no body | Changed to `suspend fun logout(@Body request: LogoutRequest)` with `LogoutRequest(refresh_token)`. Added `LogoutRequest` data class. Changed return type from `ApiResponse<Unit>` to `ApiResponse<MessageResponse>`. Updated `DashboardViewModel.logout()` to pass the stored refresh token. |

### 3. Product Endpoints

**Status:** FIXED (3 issues)

| Endpoint | Issue | Fix |
|---|---|---|
| `GET /products` | No issues | Query params `page`, `per_page`, `search`, `category_id`, `status` all match PHP. |
| `GET /products/{id}` | No issues | Returns `Product` with `stock_status` field. Stock status values `in_stock`, `low_stock`, `out_of_stock` are strings returned by PHP. |
| `POST /products` | Return type was `ApiResponse<Product>` but PHP returns `{product_id: int}` | Changed to `ApiResponse<CreateProductResponse>`. Added `CreateProductResponse` data class. |
| `PUT /products/{id}` | Return type was `ApiResponse<Product>` and body type was `CreateProductRequest` | Changed body to `UpdateProductRequest` (all fields nullable except `version` for optimistic locking). Changed return to `ApiResponse<MessageResponse>`. |
| `DELETE /products/{id}` | Return type was `ApiResponse<Unit>` but PHP returns `{message: "..."}` | Changed to `ApiResponse<MessageResponse>`. |

### 4. Sales Endpoints

**Status:** PASS (was already correct)

| Endpoint | Status |
|---|---|
| `POST /sales` | `CreateSaleRequest` includes `entry_mode`, `payment_method`, `items[]`, optional `customer_id`. Return type `CreateSaleResponse` with `sale_id`. All match PHP. |
| `GET /sales` | Query params `page`, `per_page`, `from`, `to` match PHP (`from`/`to` not `from_date`/`to_date`). |
| `GET /sales/{id}` | Returns `Sale` with items -- matches PHP response. |

### 5. Customer Endpoints

**Status:** FIXED (2 issues)

| Endpoint | Issue | Fix |
|---|---|---|
| `GET /customers` | No issues | Query params `page`, `per_page`, `search` match PHP. |
| `POST /customers` | Return type was `ApiResponse<Customer>` but PHP returns `{customer_id: int}` | Changed to `ApiResponse<CreateCustomerResponse>`. Added `CreateCustomerResponse` data class. |
| `GET /customers/{id}/credits` | Return type was `ApiResponse<List<CustomerCredit>>` but PHP returns nested `{customer, credits, payment_history}` | Changed to `ApiResponse<CustomerCreditDetail>`. Added `CustomerCreditDetail` wrapper and `CustomerPayment` data class. |
| `POST /customers/{id}/payments` | Return type was `ApiResponse<Unit>` but PHP returns `{message: "..."}` | Changed to `ApiResponse<MessageResponse>`. Request body `{amount, payment_method, notes}` already matched. |

### 6. Dashboard Endpoint

**Status:** PASS (was already correct)

| Endpoint | Status |
|---|---|
| `GET /dashboard` | Maps to `DashboardApiController@summary`. Returns `DashboardSummary` with all KPI fields matching PHP `getAllStats()` output. |
| `GET /dashboard/chart` | Query params `type` and `period` match PHP. Returns `ChartData` with `labels` and `amounts`. |

### 7. JWT Interceptor & Token Refresh

**Status:** PASS (was already correct)

- `AuthInterceptor.kt` correctly adds `Authorization: Bearer {token}` header
- Skips auth header for `/auth/login` and `/auth/refresh` paths
- `TokenRefreshAuthenticator.kt` handles 401 by:
  1. Reading refresh token from `TokenStore`
  2. Making a raw OkHttp POST to `/api/v1/auth/refresh` with `RefreshRequest` body
  3. Parsing response as `ApiResponse<AuthData>`
  4. Saving new tokens via `TokenStore.saveAuth()`
  5. Retrying the original request with the new access token
  6. Clearing tokens on failure (forcing re-login)
- Thread-safe with `synchronized` block and `isRefreshing` flag

---

## Files Modified

| File | Changes |
|---|---|
| `data/remote/ApiService.kt` | Fixed `logout()` signature (added body), fixed return types for `createProduct`, `updateProduct`, `deleteProduct`, `createCustomer`, `getCustomerCredits`, `recordPayment`, `logout` |
| `data/remote/models/AuthModels.kt` | Added `LogoutRequest`, `MessageResponse`. Made `AuthData.refreshToken` nullable |
| `data/remote/models/ProductModels.kt` | Added `UpdateProductRequest`, `CreateProductResponse` |
| `data/remote/models/CustomerModels.kt` | Added `CreateCustomerResponse`, `CustomerCreditDetail`, `CustomerPayment` |
| `data/local/TokenStore.kt` | Updated `saveAuth()` to handle nullable `refreshToken` |
| `ui/screens/dashboard/DashboardViewModel.kt` | Updated `logout()` to pass `LogoutRequest` with refresh token |

---

## Base URL Configuration

Configured in `app/build.gradle.kts` via `BuildConfig`:
- **Debug:** `http://10.0.2.2/kinarahub/api/v1/` (emulator pointing to XAMPP on host)
- **Release:** `https://your-production-url.com/api/v1/` (placeholder)

No separate `AppConfig.kt` needed -- `NetworkModule.kt` reads `BuildConfig.API_BASE_URL`.

---

## Known Limitations

1. App requires Android emulator (or physical device on same network) running against XAMPP on host machine
2. Emulator uses `10.0.2.2` to reach host localhost; physical devices need the host's LAN IP
3. Release URL is a placeholder that must be updated before production deployment
4. No offline caching -- all data requires active network connection
5. PDF invoice viewing not yet implemented (would need WebView or download + intent)
