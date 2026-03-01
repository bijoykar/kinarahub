# KinaraHub Android App

Native Android client for Kinara Store Hub -- a multi-tenant SaaS platform for inventory and sales management. Built with Kotlin and Jetpack Compose, targeting Android 8.0+ (API 26).

## Requirements

- Android Studio Hedgehog (2023.1.1) or later
- JDK 17
- Android SDK 34 (compileSdk)
- Minimum SDK 26 (Android 8.0 Oreo)
- Gradle 8.7

## Dependencies

| Library | Version | Purpose |
|---|---|---|
| Jetpack Compose BOM | 2024.09.00 | Declarative UI framework |
| Material 3 | (BOM-managed) | Material Design 3 components |
| Navigation Compose | 2.8.1 | Type-safe screen navigation |
| Retrofit 2 | 2.11.0 | HTTP client for REST API |
| OkHttp 3 | 4.12.0 | HTTP engine with interceptors |
| Hilt | 2.52 | Dependency injection |
| EncryptedSharedPreferences | 1.1.0-alpha06 | Secure JWT token storage |
| MPAndroidChart | 3.1.0 | Sales trend line chart |
| Coroutines | 1.8.1 | Async operations |
| Lifecycle ViewModel | 2.8.6 | MVVM state management |
| MockWebServer | 4.12.0 | Unit testing HTTP layer |

All dependencies are managed via `app/build.gradle.kts`. Gradle syncs automatically on project open.

## Project Setup

### 1. Clone and open

```bash
# Open in Android Studio
File > Open > C:\xampp\htdocs\kinarahub\mobile\android
# Wait for Gradle sync to complete
```

### 2. Configure API base URL

The API base URL is set per build type in `app/build.gradle.kts`:

```kotlin
defaultConfig {
    // Emulator — 10.0.2.2 maps to host machine's localhost
    buildConfigField("String", "API_BASE_URL", "\"http://10.0.2.2/kinarahub/api/v1/\"")
}

buildTypes {
    release {
        buildConfigField("String", "API_BASE_URL", "\"https://your-production-url.com/api/v1/\"")
    }
}
```

Access at runtime via `BuildConfig.API_BASE_URL` -- injected through `NetworkModule.kt`.

### 3. Run on emulator

1. Create an emulator in Android Studio: Tools > Device Manager > Create Device
2. Select a Pixel device with API 34
3. Press `Shift+F10` or click the Run button
4. Ensure XAMPP Apache + MySQL are running on the host machine

**Emulator URL:** `http://10.0.2.2/kinarahub/api/v1/` -- Android emulator maps `10.0.2.2` to the host machine's `127.0.0.1`. This is the default in `BuildConfig`.

### 4. Run on physical device

1. Enable Developer Options and USB Debugging on the device
2. Connect via USB and select the device as the run target
3. Update `API_BASE_URL` to your machine's LAN IP:

```kotlin
buildConfigField("String", "API_BASE_URL", "\"http://192.168.x.x/kinarahub/api/v1/\"")
```

`localhost` and `10.0.2.2` will not work on a physical device -- use the host machine's LAN IP. Ensure Apache listens on `0.0.0.0`, not just `127.0.0.1`.

**Note:** `android:usesCleartextTraffic="true"` is set in `AndroidManifest.xml` for local HTTP development. Remove or add a network security config for production HTTPS-only enforcement.

## Running Unit Tests

```bash
# From Android Studio
Right-click test directory > Run Tests
# Or Ctrl+Shift+F10 on a test file

# From command line
cd C:\xampp\htdocs\kinarahub\mobile\android
./gradlew test
```

**Test coverage (39 tests across 4 files):**

| File | Tests | What it covers |
|---|---|---|
| `StockStatusUtilTest` | 11 | All boundary cases: qty=0 (out), qty<reorder (low), qty==reorder (low), qty>reorder (in), fractional qty, zero reorder point, negative qty |
| `CartCalculationTest` | 10 | Line totals, 3-item subtotal (525), remove middle (225), update qty (325), empty cart, variant pricing, large cart (20 items) |
| `CreditSaleValidationTest` | 11 | Credit+no customer (error), credit+customer (pass), cash/upi/card without customer (pass), empty cart, exact error message validation |
| `TokenRefreshAuthenticatorTest` | 7 | MockWebServer: successful refresh + retry, failed refresh + token clear, retry guard header, concurrent thread handling, refresh URL construction |

Tests use `FakeTokenStore` (implements `TokenStore` interface) and `MockWebServer` for real HTTP semantics -- no Android context or Robolectric needed.

## Architecture

### Pattern: MVVM

```
Composable (UI) --> ViewModel (StateFlow) --> ApiService (Retrofit) --> REST API
                                          --> TokenStore (EncryptedSharedPreferences) --> Keychain
```

- **Composables** -- Jetpack Compose UI, stateless rendering, delegate actions to ViewModels
- **ViewModels** -- `@HiltViewModel`, expose `StateFlow` for reactive UI state
- **Network** -- Retrofit + OkHttp with interceptors for JWT and token refresh
- **Models** -- Data classes with `@SerializedName` annotations matching the API envelope

### Dependency Injection (Hilt)

```kotlin
// NetworkModule.kt — provides OkHttpClient, Retrofit, ApiService
// AppModule.kt — binds TokenStore interface to TokenStoreImpl
```

All ViewModels use `@Inject constructor` -- Hilt handles the graph automatically.

### Navigation

`NavHost` with sealed class routes:

```kotlin
sealed class Screen(val route: String) {
    data object Login : Screen("login")
    data object Dashboard : Screen("dashboard")
    data object Inventory : Screen("inventory")
    data object ProductDetail : Screen("product/{productId}")
    data object POSSale : Screen("pos")
    data object SalesHistory : Screen("sales")
    data object Customers : Screen("customers")
}
```

Bottom `NavigationBar` with 5 tabs: Dashboard, Inventory, POS, Sales, Customers.

### JWT Token Refresh Flow

```
Request with access_token
    |
    v
Server returns 401?
    |-- No --> Process response normally
    |-- Yes --> POST /auth/refresh with refresh_token
                    |
                    v
                Refresh succeeds?
                    |-- Yes --> Save new tokens, retry original request
                    |           (X-Retry-After-Refresh header prevents loops)
                    |-- No  --> Clear EncryptedSharedPreferences
                                --> TokenStore.isLoggedIn = false
                                --> App navigates to LoginScreen
```

- Access token: 15-minute lifetime
- Refresh token: 30-day lifetime, rotated on each use
- Tokens stored in `EncryptedSharedPreferences` (AES-256-GCM)
- Refresh is guarded with `synchronized` block and `@Volatile isRefreshing` flag for thread safety
- `X-Retry-After-Refresh` header prevents infinite 401 retry loops

### Stock Status Colors

| Status | Condition | Color |
|---|---|---|
| In Stock | `qty > reorder_point` | Green (#16A34A) |
| Low Stock | `0 < qty <= reorder_point` | Amber (#D97706) |
| Out of Stock | `qty == 0` | Red (#DC2626) |

Computed on read via `StockStatusUtil.computeStockStatus()` -- not stored.

### API Response Envelope

All API responses follow this structure:

```json
{
    "success": true,
    "data": { ... },
    "meta": { "page": 1, "per_page": 20, "total": 150 },
    "error": null
}
```

Mapped to `ApiResponse<T>` in Kotlin with Gson deserialization.

## Project Structure

```
mobile/android/
  settings.gradle.kts                          -- Project settings, repositories
  build.gradle.kts                             -- Root plugins (AGP, Kotlin, Hilt)
  gradle.properties                            -- JVM args, AndroidX config
  gradle/wrapper/
    gradle-wrapper.properties                  -- Gradle 8.7 distribution
  app/
    build.gradle.kts                           -- App dependencies, BuildConfig, compose
    proguard-rules.pro                         -- Retrofit/Gson/OkHttp keep rules
    src/main/
      AndroidManifest.xml                      -- Permissions, Hilt app, cleartext traffic
      res/values/
        strings.xml                            -- App strings, currency symbol
        colors.xml                             -- Brand + stock status + semantic colors
        themes.xml                             -- Material theme, status bar color
      java/com/kinarahub/
        KinaraHubApp.kt                        -- @HiltAndroidApp entry point
        MainActivity.kt                        -- @AndroidEntryPoint, NavHost setup
        di/
          NetworkModule.kt                     -- Hilt: OkHttpClient, Retrofit, ApiService
          AppModule.kt                         -- Hilt: TokenStore interface binding
        data/
          local/
            TokenStore.kt                      -- Interface + EncryptedSharedPreferences impl
          remote/
            ApiService.kt                      -- Retrofit interface (17 endpoints)
            AuthInterceptor.kt                 -- OkHttp Interceptor (Bearer header)
            TokenRefreshAuthenticator.kt       -- OkHttp Authenticator (401 refresh)
            models/
              ApiResponse.kt                   -- Standard envelope + Meta
              AuthModels.kt                    -- LoginRequest, RefreshRequest, AuthData
              ProductModels.kt                 -- Product, ProductVariant, CreateProductRequest
              SaleModels.kt                    -- Sale, SaleItem, CreateSaleRequest
              CustomerModels.kt                -- Customer, CustomerCredit, RecordPaymentRequest
              DashboardModels.kt               -- DashboardSummary, TopProduct, SalesTrend
        util/
          StockStatusUtil.kt                   -- Testable stock status computation
        ui/
          theme/
            Theme.kt                           -- Material 3 light/dark color schemes
          components/
            CommonComponents.kt                -- StockBadge, PaymentMethodBadge, KpiCard, etc.
            SalesTrendChart.kt                 -- MPAndroidChart LineChart composable
          navigation/
            NavGraph.kt                        -- Route sealed class definitions
            KinaraNavHost.kt                   -- NavHost with all screen destinations
          screens/
            login/
              LoginViewModel.kt                -- Login state, API call, error handling
              LoginScreen.kt                   -- Email + password form, visibility toggle
            dashboard/
              DashboardViewModel.kt            -- KPI summary, sales trend period loading
              DashboardScreen.kt               -- KPI cards, chart, top products, recent sales
            inventory/
              InventoryViewModel.kt            -- Product list, search, pagination
              InventoryListScreen.kt           -- Searchable list with stock badges
              ProductDetailViewModel.kt        -- Single product fetch
              ProductDetailScreen.kt           -- Product info, pricing, variants
            sales/
              POSSaleViewModel.kt              -- Cart management, validation, sale submission
              POSSaleScreen.kt                 -- Product search, cart, payment, customer picker
              SalesHistoryViewModel.kt         -- Paginated sales list
              SalesHistoryScreen.kt            -- Sales history with payment badges
            customers/
              CustomerViewModel.kt             -- Customer list, search, pagination
              CustomerListScreen.kt            -- Customer list with outstanding balance
    src/test/java/com/kinarahub/
      util/
        StockStatusUtilTest.kt                 -- 11 stock status boundary tests
      ui/screens/sales/
        CartCalculationTest.kt                 -- 10 cart math tests
        CreditSaleValidationTest.kt            -- 11 credit sale validation tests
      data/remote/
        TokenRefreshAuthenticatorTest.kt       -- 7 MockWebServer token refresh tests
  API_INTEGRATION.md                           -- Full API endpoint docs, edge cases, flow diagrams
```

## Screens

| # | Screen | Endpoint | Features |
|---|---|---|---|
| 1 | Login | POST /auth/login | Email + password, visibility toggle, error display, auto-focus |
| 2 | Dashboard | GET /dashboard/summary | KPI cards, stock alerts, MPAndroidChart trend with period selector, top products, recent sales, bottom nav |
| 3 | Inventory List | GET /products | Search by name/SKU, stock color badges, infinite scroll pagination |
| 4 | Product Detail | GET /products/{id} | Pricing, stock info, category, UOM, variants list |
| 5 | POS Sale | POST /sales | Product search, cart with qty +/- controls, payment method chips, customer dropdown for credit, 409 conflict handling |
| 6 | Sales History | GET /sales | Paginated list, payment method badges, customer name, sale number |
| 7 | Customer List | GET /customers | Outstanding balance in red, phone number, email, search |

## Integration Testing

See `API_INTEGRATION.md` for full endpoint documentation, token refresh flow diagrams, edge case handling (409/422/401/timeout), and URL configuration guide.

The iOS team's `PostmanCollection.json` at `mobile/ios/PostmanCollection.json` covers all 15 API endpoints with example request/response bodies including error cases.
