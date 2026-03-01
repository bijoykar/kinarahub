# KinaraHub iOS App

Native iOS client for Kinara Store Hub — a multi-tenant SaaS platform for inventory and sales management. Built with Swift and SwiftUI, targeting iOS 16.0+.

## Requirements

- Xcode 15.0 or later
- iOS 16.0 deployment target (required for Swift Charts)
- macOS Ventura 13.0 or later (for Xcode 15)
- Swift 5.9

## Dependencies (Swift Package Manager)

| Package | Version | Purpose |
|---|---|---|
| [KeychainAccess](https://github.com/kishikawakatsumi/KeychainAccess) | 4.2.2+ | Secure JWT token storage in iOS Keychain |

SPM resolves automatically when you open the project in Xcode or build via `Package.swift`.

## Project Setup

### 1. Clone and open

```bash
cd C:\xampp\htdocs\kinarahub\mobile\ios
open KinaraHub.xcodeproj
# Or open Package.swift in Xcode for SPM-based workflow
```

### 2. Configure API base URL

The API base URL is set per build configuration in `KinaraHub/Config/AppConfig.swift`:

```swift
#if DEBUG
static let apiBaseURL = "http://localhost/kinarahub/api/v1"
#else
static let apiBaseURL = "https://api.kinarahub.com/api/v1"
#endif
```

Alternatively, use the xcconfig files:

- `KinaraHub/Config/KinaraHub-Debug.xcconfig` — local development
- `KinaraHub/Config/KinaraHub-Release.xcconfig` — production

To point at a different server during development, edit the `API_BASE_URL` value in the Debug xcconfig or update the `#if DEBUG` block in `AppConfig.swift`.

### 3. Run on Simulator

1. Select a simulator target (iPhone 15, iPhone 15 Pro, etc.) in the Xcode toolbar
2. Press `Cmd+R` to build and run
3. The app will launch on the Login screen

For the local XAMPP backend, ensure Apache and MySQL are running and the API is accessible at `http://localhost/kinarahub/api/v1/`.

**Note:** iOS Simulator can reach `localhost` directly. No special host mapping is needed (unlike Android's `10.0.2.2`).

### 4. Run on physical device

1. Connect your iPhone via USB
2. Select it as the run destination in Xcode
3. You may need to trust the developer certificate on the device: Settings > General > VPN & Device Management
4. Update `API_BASE_URL` to your machine's local IP (e.g., `http://192.168.1.100/kinarahub/api/v1`) since `localhost` will not resolve to your dev machine from a physical device

## Running Unit Tests

```bash
# From Xcode
Cmd+U  (runs all tests)

# Or from command line (requires xcodebuild)
xcodebuild test -scheme KinaraHub -destination 'platform=iOS Simulator,name=iPhone 15'
```

Test file: `KinaraHub/Tests/ViewModelTests.swift`

**Test coverage (23 tests across 5 classes):**

| Class | Tests | What it covers |
|---|---|---|
| `AuthViewModelTests` | 4 | Login success/failure, empty fields validation, logout clears tokens |
| `InventoryViewModelTests` | 8 | Stock status computation (out of stock, low stock, in stock), fractional quantities, variant status, API list loading |
| `SalesViewModelTests` | 7 | Credit sale without customer validation, empty cart validation, cart total across multiple items, quantity deduplication, 409 conflict error, remove/clear cart |
| `CustomerViewModelTests` | 6 | Outstanding balance highlighting, partial payment flow, create customer, search filtering by name and mobile |
| `ModelDecodingTests` | 4 | API envelope decoding, error response, pagination meta, sales trend response |

Tests use `MockAPIClient` (conforms to `APIClientProtocol`) and `MockTokenManager` (conforms to `TokenManagerProtocol`) — no live server needed.

## Architecture

### Pattern: MVVM

```
View (SwiftUI) --> ViewModel (@MainActor, @Published) --> APIClient --> REST API
                                                      --> TokenManager --> Keychain
```

- **Views** — SwiftUI views, stateless rendering, delegate actions to ViewModels
- **ViewModels** — `@MainActor`, `ObservableObject` with `@Published` properties, all async/await
- **Network** — `APIClient` wraps URLSession, handles JSON encoding/decoding and token refresh
- **Models** — `Codable` structs matching the API response envelope

### Dependency Injection

All ViewModels accept protocol-based dependencies via optional init parameters:

```swift
// Production (default)
let vm = AuthViewModel()

// Testing
let vm = AuthViewModel(apiClient: mockAPI, tokenManager: mockTokens)
```

Protocols: `APIClientProtocol`, `TokenManagerProtocol`

### Navigation

`NavigationStack` with type-safe routing via `enum Route: Hashable`:

```swift
enum Route: Hashable {
    case login
    case dashboard
    case inventoryList
    case productDetail(id: Int)
    case posSale
    case salesHistory
    case customerList
}
```

`TabView` with 5 tabs: Dashboard, Inventory, POS, Sales, Customers.

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
                    |-- No  --> Clear Keychain, post authSessionExpired notification
                                --> App navigates to LoginView
```

- Access token: 15-minute lifetime
- Refresh token: 30-day lifetime, rotated on use
- Tokens stored in iOS Keychain via KeychainAccess
- Refresh is guarded against concurrent attempts (`isRefreshing` flag)

### Stock Status Colors

| Status | Condition | Color |
|---|---|---|
| In Stock | `qty > reorder_point` | Green |
| Low Stock | `0 < qty <= reorder_point` | Orange |
| Out of Stock | `qty == 0` | Red |

Computed on read from `stockQuantity` and `reorderPoint` fields — not stored.

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

Mapped to `APIResponse<T: Codable>` in Swift.

## Project Structure

```
mobile/ios/
  Package.swift                          -- SPM package definition
  PostmanCollection.json                 -- API endpoint collection for testing
  KinaraHub.xcodeproj/
    project.pbxproj
  KinaraHub/
    App/
      KinaraHubApp.swift                 -- @main entry point
      ContentView.swift                  -- Auth gate + TabView + route destinations
    Config/
      AppConfig.swift                    -- API URL, currency, pagination defaults
      KinaraHub-Debug.xcconfig
      KinaraHub-Release.xcconfig
    Network/
      APIClient.swift                    -- URLSession wrapper, token refresh, APIClientProtocol
      APIEndpoints.swift                 -- All endpoint URL builders
      TokenManager.swift                 -- Keychain JWT storage, TokenManagerProtocol
      Models/
        APIModels.swift                  -- All Codable structs and enums
    Navigation/
      AppRouter.swift                    -- Route enum, Tab enum, NavigationPath
    ViewModels/
      AuthViewModel.swift                -- Login, logout, session state
      DashboardViewModel.swift           -- KPI summary, sales trend chart data
      InventoryViewModel.swift           -- Product list, search, pagination, detail
      SalesViewModel.swift               -- POS cart, sale submission, sales history
      CustomerViewModel.swift            -- Customer CRUD, credit history, payments
    Views/
      Auth/
        LoginView.swift                  -- Email + password login form
      Dashboard/
        DashboardView.swift              -- KPI cards, stock alerts, top products, recent sales
        SalesTrendChart.swift            -- Swift Charts line chart with period picker
      Inventory/
        InventoryListView.swift          -- Searchable product list with stock badges
        ProductDetailView.swift          -- Product info, pricing, stock, variants
      Sales/
        POSSaleView.swift                -- Product search, cart, payment, customer picker
        SalesHistoryView.swift           -- Paginated sales list with date filter
      Customers/
        CustomerListView.swift           -- Customer list, create form, record payment
      Components/
        ErrorStateView.swift             -- Error and empty state displays
        StockBadge.swift                 -- Green/orange/red stock status badge
    Tests/
      ViewModelTests.swift               -- 23 unit tests with MockAPIClient
    Assets.xcassets/
      Contents.json
      AppIcon.appiconset/Contents.json
      AccentColor.colorset/Contents.json
```

## Screens

| # | Screen | Endpoint | Features |
|---|---|---|---|
| 1 | Login | POST /auth/login | Email + password, error display, auto-focus |
| 2 | Dashboard | GET /dashboard/summary | KPI cards, stock alerts, Swift Charts trend, top products, recent sales, pull-to-refresh |
| 3 | Inventory List | GET /products | Search, stock color badges, infinite scroll pagination |
| 4 | Product Detail | GET /products/{id} | Pricing, stock info, variants list |
| 5 | POS Sale | POST /sales | Product search, cart with qty stepper, payment method, customer picker for credit, 409 conflict handling |
| 6 | Sales History | GET /sales | Paginated list, date range filter, payment badges |
| 7 | Customer List | GET /customers | Outstanding balance highlight, create customer, record payment |

## Integration Testing

Import `PostmanCollection.json` into Postman to test all 15 API endpoints with example request/response bodies. The collection includes success and error cases (401, 404, 409, 422).
