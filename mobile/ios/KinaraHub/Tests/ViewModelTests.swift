import XCTest
@testable import KinaraHub

// MARK: - Mock APIClient

@MainActor
final class MockAPIClient: APIClientProtocol {
    var getHandler: ((String, [String: String]?) async throws -> Any)?
    var postHandler: ((String, (any Encodable)?) async throws -> Any)?
    var putHandler: ((String, (any Encodable)?) async throws -> Any)?
    var deleteHandler: ((String) async throws -> Any)?

    func get<T: Codable>(url: String, queryParams: [String: String]? = nil, authenticated: Bool = true) async throws -> APIResponse<T> {
        guard let handler = getHandler else {
            throw APIError.noData
        }
        let result = try await handler(url, queryParams)
        guard let typed = result as? APIResponse<T> else {
            throw APIError.noData
        }
        return typed
    }

    func post<T: Codable>(url: String, body: (any Encodable)? = nil, authenticated: Bool = true) async throws -> APIResponse<T> {
        guard let handler = postHandler else {
            throw APIError.noData
        }
        let result = try await handler(url, body)
        guard let typed = result as? APIResponse<T> else {
            throw APIError.noData
        }
        return typed
    }

    func put<T: Codable>(url: String, body: (any Encodable)? = nil, authenticated: Bool = true) async throws -> APIResponse<T> {
        guard let handler = putHandler else {
            throw APIError.noData
        }
        let result = try await handler(url, body)
        guard let typed = result as? APIResponse<T> else {
            throw APIError.noData
        }
        return typed
    }

    func delete<T: Codable>(url: String, authenticated: Bool = true) async throws -> APIResponse<T> {
        guard let handler = deleteHandler else {
            throw APIError.noData
        }
        let result = try await handler(url)
        guard let typed = result as? APIResponse<T> else {
            throw APIError.noData
        }
        return typed
    }
}

// MARK: - Mock TokenManager

@MainActor
final class MockTokenManager: TokenManagerProtocol {
    var isAuthenticated: Bool = false
    var accessToken: String?
    var refreshToken: String?

    func saveTokens(accessToken: String, refreshToken: String) {
        self.accessToken = accessToken
        self.refreshToken = refreshToken
        isAuthenticated = true
    }

    func clearTokens() {
        accessToken = nil
        refreshToken = nil
        isAuthenticated = false
    }
}

// MARK: - Test Helpers

private func decodeJSON<T: Codable>(_ json: String) -> T {
    let data = json.data(using: .utf8)!
    return try! JSONDecoder().decode(T.self, from: data)
}

// MARK: - AuthViewModel Tests

@MainActor
final class AuthViewModelTests: XCTestCase {

    func testLoginSuccess_SetsTokensAndAuthenticated() async {
        let mockAPI = MockAPIClient()
        let mockTokens = MockTokenManager()
        let vm = AuthViewModel(apiClient: mockAPI, tokenManager: mockTokens)

        mockAPI.postHandler = { url, _ in
            let response: APIResponse<LoginResponse> = APIResponse(
                success: true,
                data: LoginResponse(
                    accessToken: "test_access_token_abc",
                    refreshToken: "test_refresh_token_xyz",
                    expiresIn: 900
                ),
                meta: nil,
                error: nil
            )
            return response
        }

        vm.email = "owner@example.com"
        vm.password = "password123"

        await vm.login()

        XCTAssertTrue(vm.isAuthenticated, "Should be authenticated after successful login")
        XCTAssertNil(vm.errorMessage, "Should have no error after successful login")
        XCTAssertEqual(mockTokens.accessToken, "test_access_token_abc")
        XCTAssertEqual(mockTokens.refreshToken, "test_refresh_token_xyz")
        XCTAssertTrue(mockTokens.isAuthenticated)
        XCTAssertEqual(vm.email, "", "Email should be cleared after login")
        XCTAssertEqual(vm.password, "", "Password should be cleared after login")
        XCTAssertFalse(vm.isLoading)
    }

    func testLoginFailure_SetsErrorMessage() async {
        let mockAPI = MockAPIClient()
        let mockTokens = MockTokenManager()
        let vm = AuthViewModel(apiClient: mockAPI, tokenManager: mockTokens)

        mockAPI.postHandler = { _, _ in
            throw APIError.serverError("Invalid email or password.")
        }

        vm.email = "wrong@example.com"
        vm.password = "wrongpass"

        await vm.login()

        XCTAssertFalse(vm.isAuthenticated, "Should not be authenticated after failed login")
        XCTAssertEqual(vm.errorMessage, "Invalid email or password.")
        XCTAssertNil(mockTokens.accessToken)
        XCTAssertFalse(vm.isLoading)
    }

    func testLoginEmptyFields_SetsValidationError() async {
        let mockAPI = MockAPIClient()
        let mockTokens = MockTokenManager()
        let vm = AuthViewModel(apiClient: mockAPI, tokenManager: mockTokens)

        vm.email = ""
        vm.password = ""

        await vm.login()

        XCTAssertFalse(vm.isAuthenticated)
        XCTAssertEqual(vm.errorMessage, "Please enter email and password.")
    }

    func testLogout_ClearsTokens() async {
        let mockAPI = MockAPIClient()
        let mockTokens = MockTokenManager()
        mockTokens.saveTokens(accessToken: "abc", refreshToken: "xyz")

        let vm = AuthViewModel(apiClient: mockAPI, tokenManager: mockTokens)

        // Mock the logout POST (should not fail even if server call fails)
        mockAPI.postHandler = { _, _ in
            let response: APIResponse<EmptyResponse> = APIResponse(
                success: true, data: nil, meta: nil, error: nil
            )
            return response
        }

        await vm.logout()

        XCTAssertFalse(vm.isAuthenticated, "Should not be authenticated after logout")
        XCTAssertNil(mockTokens.accessToken, "Access token should be cleared")
        XCTAssertNil(mockTokens.refreshToken, "Refresh token should be cleared")
        XCTAssertFalse(mockTokens.isAuthenticated)
        XCTAssertFalse(vm.isLoading)
    }
}

// MARK: - InventoryViewModel Tests (Stock Status)

@MainActor
final class InventoryViewModelTests: XCTestCase {

    func testStockStatus_OutOfStock_WhenQuantityZero() {
        let product = Product(
            id: 1, storeId: 1, sku: "SKU001", name: "Test Product",
            categoryId: nil, categoryName: nil, uomId: nil, uomName: nil,
            uomAbbreviation: nil, sellingPrice: "100.00", costPrice: "80.00",
            stockQuantity: "0.000", reorderPoint: "5.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        XCTAssertEqual(product.stockStatus, .outOfStock,
            "Product with qty=0 should be Out of Stock")
    }

    func testStockStatus_LowStock_WhenQuantityEqualsReorderPoint() {
        let product = Product(
            id: 2, storeId: 1, sku: "SKU002", name: "Low Stock Product",
            categoryId: nil, categoryName: nil, uomId: nil, uomName: nil,
            uomAbbreviation: nil, sellingPrice: "50.00", costPrice: "40.00",
            stockQuantity: "5.000", reorderPoint: "5.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        XCTAssertEqual(product.stockStatus, .lowStock,
            "Product with qty == reorder_point should be Low Stock")
    }

    func testStockStatus_LowStock_WhenQuantityBelowReorderPoint() {
        let product = Product(
            id: 3, storeId: 1, sku: "SKU003", name: "Very Low Stock",
            categoryId: nil, categoryName: nil, uomId: nil, uomName: nil,
            uomAbbreviation: nil, sellingPrice: "200.00", costPrice: "150.00",
            stockQuantity: "2.000", reorderPoint: "10.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        XCTAssertEqual(product.stockStatus, .lowStock,
            "Product with 0 < qty < reorder_point should be Low Stock")
    }

    func testStockStatus_InStock_WhenQuantityAboveReorderPoint() {
        let product = Product(
            id: 4, storeId: 1, sku: "SKU004", name: "Well Stocked",
            categoryId: nil, categoryName: nil, uomId: nil, uomName: nil,
            uomAbbreviation: nil, sellingPrice: "300.00", costPrice: "250.00",
            stockQuantity: "50.000", reorderPoint: "10.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        XCTAssertEqual(product.stockStatus, .inStock,
            "Product with qty > reorder_point should be In Stock")
    }

    func testStockStatus_FractionalQuantity() {
        let product = Product(
            id: 5, storeId: 1, sku: "SKU005", name: "Fractional Stock",
            categoryId: nil, categoryName: nil, uomId: nil, uomName: nil,
            uomAbbreviation: nil, sellingPrice: "100.00", costPrice: "80.00",
            stockQuantity: "0.500", reorderPoint: "1.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        XCTAssertEqual(product.stockStatus, .lowStock,
            "Product with fractional qty 0.5 < reorder 1.0 should be Low Stock")
    }

    func testVariantStockStatus_OutOfStock() {
        let variant = ProductVariant(
            id: 1, productId: 1, variantName: "500ml",
            sku: "SKU001-500ML", sellingPrice: "50.00",
            costPrice: "40.00", stockQuantity: "0.000",
            reorderPoint: "3.000", version: 0
        )

        XCTAssertEqual(variant.stockStatus, .outOfStock,
            "Variant with qty=0 should be Out of Stock")
    }

    func testVariantStockStatus_InStock() {
        let variant = ProductVariant(
            id: 2, productId: 1, variantName: "1L",
            sku: "SKU001-1L", sellingPrice: "90.00",
            costPrice: "70.00", stockQuantity: "20.000",
            reorderPoint: "5.000", version: 0
        )

        XCTAssertEqual(variant.stockStatus, .inStock,
            "Variant with qty > reorder should be In Stock")
    }

    func testLoadProducts_PopulatesList() async {
        let mockAPI = MockAPIClient()
        let vm = InventoryViewModel(apiClient: mockAPI)

        mockAPI.getHandler = { _, _ in
            let response: APIResponse<[Product]> = APIResponse(
                success: true,
                data: [
                    Product(
                        id: 1, storeId: 1, sku: "SKU001", name: "Rice",
                        categoryId: 1, categoryName: "Grains", uomId: 1,
                        uomName: "Kg", uomAbbreviation: "Kg",
                        sellingPrice: "450.00", costPrice: "380.00",
                        stockQuantity: "25.000", reorderPoint: "5.000",
                        status: "active", version: 1, variants: nil,
                        createdAt: nil, updatedAt: nil
                    )
                ],
                meta: Meta(page: 1, perPage: 20, total: 1),
                error: nil
            )
            return response
        }

        await vm.loadProducts()

        XCTAssertEqual(vm.products.count, 1)
        XCTAssertEqual(vm.products.first?.sku, "SKU001")
        XCTAssertEqual(vm.totalProducts, 1)
        XCTAssertNil(vm.errorMessage)
    }
}

// MARK: - SalesViewModel Tests

@MainActor
final class SalesViewModelTests: XCTestCase {

    func testCreditSaleWithoutCustomer_FailsValidation() async {
        let mockAPI = MockAPIClient()
        let vm = SalesViewModel(apiClient: mockAPI)

        // Add an item to the cart
        let product = Product(
            id: 1, storeId: 1, sku: "SKU001", name: "Rice",
            categoryId: nil, categoryName: nil, uomId: nil,
            uomName: nil, uomAbbreviation: nil,
            sellingPrice: "450.00", costPrice: "380.00",
            stockQuantity: "25.000", reorderPoint: "5.000",
            status: "active", version: 1, variants: nil,
            createdAt: nil, updatedAt: nil
        )
        vm.addToCart(product: product, quantity: 2)

        // Select credit payment, no customer
        vm.selectedPaymentMethod = .credit
        vm.selectedCustomer = nil

        await vm.submitSale()

        XCTAssertEqual(vm.validationError, "Customer required for credit sales.",
            "Credit sale without customer should fail validation")
        XCTAssertFalse(vm.showSaleSuccess)
        XCTAssertFalse(vm.isCartEmpty, "Cart should NOT be cleared on validation failure")
    }

    func testEmptyCart_FailsValidation() async {
        let mockAPI = MockAPIClient()
        let vm = SalesViewModel(apiClient: mockAPI)

        await vm.submitSale()

        XCTAssertEqual(vm.validationError, "Cart is empty. Add items to continue.")
    }

    func testCartTotal_ComputesCorrectly_MultipleItems() {
        let mockAPI = MockAPIClient()
        let vm = SalesViewModel(apiClient: mockAPI)

        let product1 = Product(
            id: 1, storeId: 1, sku: "SKU001", name: "Rice 5kg",
            categoryId: nil, categoryName: nil, uomId: nil,
            uomName: nil, uomAbbreviation: nil,
            sellingPrice: "450.00", costPrice: nil,
            stockQuantity: "100.000", reorderPoint: "10.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        let product2 = Product(
            id: 2, storeId: 1, sku: "SKU002", name: "Dal 1kg",
            categoryId: nil, categoryName: nil, uomId: nil,
            uomName: nil, uomAbbreviation: nil,
            sellingPrice: "160.00", costPrice: nil,
            stockQuantity: "50.000", reorderPoint: "5.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        let product3 = Product(
            id: 3, storeId: 1, sku: "SKU003", name: "Sugar 1kg",
            categoryId: nil, categoryName: nil, uomId: nil,
            uomName: nil, uomAbbreviation: nil,
            sellingPrice: "55.00", costPrice: nil,
            stockQuantity: "200.000", reorderPoint: "20.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        vm.addToCart(product: product1, quantity: 2)  // 450 x 2 = 900
        vm.addToCart(product: product2, quantity: 3)  // 160 x 3 = 480
        vm.addToCart(product: product3, quantity: 1)  // 55 x 1 = 55

        // Total should be 900 + 480 + 55 = 1435
        XCTAssertEqual(vm.cartTotal, Decimal(1435),
            "Cart total should be 1435 (900 + 480 + 55)")
        XCTAssertEqual(vm.cartSubtotal, Decimal(1435))
        XCTAssertEqual(vm.cartItems.count, 3)
        XCTAssertFalse(vm.isCartEmpty)
    }

    func testAddToCart_SameProduct_IncrementsQuantity() {
        let mockAPI = MockAPIClient()
        let vm = SalesViewModel(apiClient: mockAPI)

        let product = Product(
            id: 1, storeId: 1, sku: "SKU001", name: "Rice",
            categoryId: nil, categoryName: nil, uomId: nil,
            uomName: nil, uomAbbreviation: nil,
            sellingPrice: "450.00", costPrice: nil,
            stockQuantity: "100.000", reorderPoint: "10.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        vm.addToCart(product: product, quantity: 2)
        vm.addToCart(product: product, quantity: 3)

        XCTAssertEqual(vm.cartItems.count, 1, "Same product should not duplicate in cart")
        XCTAssertEqual(vm.cartItems[0].quantity, Decimal(5), "Quantity should be 2 + 3 = 5")
        XCTAssertEqual(vm.cartTotal, Decimal(2250), "Total: 450 x 5 = 2250")
    }

    func testConflictResponse_ShowsConflictError() async {
        let mockAPI = MockAPIClient()
        let vm = SalesViewModel(apiClient: mockAPI)

        let product = Product(
            id: 1, storeId: 1, sku: "SKU001", name: "Rice",
            categoryId: nil, categoryName: nil, uomId: nil,
            uomName: nil, uomAbbreviation: nil,
            sellingPrice: "450.00", costPrice: nil,
            stockQuantity: "25.000", reorderPoint: "5.000",
            status: "active", version: 1, variants: nil,
            createdAt: nil, updatedAt: nil
        )
        vm.addToCart(product: product, quantity: 1)
        vm.selectedPaymentMethod = .cash

        // Mock 409 conflict
        mockAPI.postHandler = { _, _ in
            throw APIError.conflict
        }

        await vm.submitSale()

        XCTAssertEqual(vm.validationError, "Stock was modified. Please refresh and try again.",
            "409 conflict should show stock modification error")
        XCTAssertFalse(vm.showSaleSuccess)
    }

    func testRemoveFromCart_RemovesItem() {
        let mockAPI = MockAPIClient()
        let vm = SalesViewModel(apiClient: mockAPI)

        let product = Product(
            id: 1, storeId: 1, sku: "SKU001", name: "Rice",
            categoryId: nil, categoryName: nil, uomId: nil,
            uomName: nil, uomAbbreviation: nil,
            sellingPrice: "450.00", costPrice: nil,
            stockQuantity: "100.000", reorderPoint: "10.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        vm.addToCart(product: product, quantity: 2)
        XCTAssertEqual(vm.cartItems.count, 1)

        vm.removeFromCart(at: 0)
        XCTAssertTrue(vm.isCartEmpty, "Cart should be empty after removing only item")
        XCTAssertEqual(vm.cartTotal, Decimal.zero)
    }

    func testUpdateQuantityToZero_RemovesItem() {
        let mockAPI = MockAPIClient()
        let vm = SalesViewModel(apiClient: mockAPI)

        let product = Product(
            id: 1, storeId: 1, sku: "SKU001", name: "Rice",
            categoryId: nil, categoryName: nil, uomId: nil,
            uomName: nil, uomAbbreviation: nil,
            sellingPrice: "450.00", costPrice: nil,
            stockQuantity: "100.000", reorderPoint: "10.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        vm.addToCart(product: product, quantity: 3)
        vm.updateCartItemQuantity(at: 0, quantity: 0)

        XCTAssertTrue(vm.isCartEmpty, "Setting quantity to 0 should remove the item")
    }

    func testClearCart_ResetsAllState() {
        let mockAPI = MockAPIClient()
        let vm = SalesViewModel(apiClient: mockAPI)

        let product = Product(
            id: 1, storeId: 1, sku: "SKU001", name: "Rice",
            categoryId: nil, categoryName: nil, uomId: nil,
            uomName: nil, uomAbbreviation: nil,
            sellingPrice: "450.00", costPrice: nil,
            stockQuantity: "100.000", reorderPoint: "10.000",
            status: "active", version: 0, variants: nil,
            createdAt: nil, updatedAt: nil
        )

        vm.addToCart(product: product, quantity: 2)
        vm.selectedPaymentMethod = .credit
        vm.selectedCustomer = Customer(
            id: 1, storeId: 1, name: "Test", mobile: nil,
            email: nil, isDefault: 0, outstandingBalance: "0.00", createdAt: nil
        )
        vm.saleNotes = "Test notes"

        vm.clearCart()

        XCTAssertTrue(vm.isCartEmpty)
        XCTAssertEqual(vm.selectedPaymentMethod, .cash, "Payment method should reset to cash")
        XCTAssertNil(vm.selectedCustomer, "Customer should be cleared")
        XCTAssertEqual(vm.saleNotes, "")
        XCTAssertNil(vm.validationError)
    }
}

// MARK: - CustomerViewModel Tests

@MainActor
final class CustomerViewModelTests: XCTestCase {

    func testOutstandingBalance_HighlightedWhenPositive() {
        let customer = Customer(
            id: 1, storeId: 1, name: "Ramesh Kumar",
            mobile: "9876543210", email: nil,
            isDefault: 0, outstandingBalance: "4500.00",
            createdAt: nil
        )

        XCTAssertTrue(customer.hasOutstandingBalance,
            "Customer with 4500.00 balance should have outstanding balance")
        XCTAssertEqual(customer.outstandingBalanceDecimal, Decimal(4500))
    }

    func testOutstandingBalance_NotHighlightedWhenZero() {
        let customer = Customer(
            id: 2, storeId: 1, name: "Suresh Patel",
            mobile: "9876543211", email: nil,
            isDefault: 0, outstandingBalance: "0.00",
            createdAt: nil
        )

        XCTAssertFalse(customer.hasOutstandingBalance,
            "Customer with 0.00 balance should NOT have outstanding balance")
    }

    func testRecordPayment_UpdatesBalanceAfterRefresh() async {
        let mockAPI = MockAPIClient()
        let vm = CustomerViewModel(apiClient: mockAPI)

        // Pre-load a customer with 4500.00 outstanding
        vm.customers = [
            Customer(
                id: 1, storeId: 1, name: "Ramesh Kumar",
                mobile: "9876543210", email: nil,
                isDefault: 0, outstandingBalance: "4500.00",
                createdAt: nil
            )
        ]

        var postCallCount = 0
        mockAPI.postHandler = { url, _ in
            postCallCount += 1
            // Record payment response
            let response: APIResponse<EmptyResponse> = APIResponse(
                success: true, data: nil, meta: nil, error: nil
            )
            return response
        }

        // After payment, the refresh (GET) will return updated balance
        mockAPI.getHandler = { _, _ in
            let response: APIResponse<[Customer]> = APIResponse(
                success: true,
                data: [
                    Customer(
                        id: 1, storeId: 1, name: "Ramesh Kumar",
                        mobile: "9876543210", email: nil,
                        isDefault: 0, outstandingBalance: "2500.00",
                        createdAt: nil
                    )
                ],
                meta: Meta(page: 1, perPage: 20, total: 1),
                error: nil
            )
            return response
        }

        vm.paymentAmount = "2000.00"
        vm.paymentMethod = .cash

        let success = await vm.recordPayment(customerId: 1)

        XCTAssertTrue(success, "Payment should succeed")
        XCTAssertEqual(postCallCount, 1, "Should have called POST once for payment")

        // After refresh, the customer list should have updated balance
        XCTAssertEqual(vm.customers.count, 1)
        XCTAssertEqual(vm.customers[0].outstandingBalanceDecimal, Decimal(2500),
            "Outstanding balance should be 2500 after 2000 partial payment on 4500")
        XCTAssertTrue(vm.customers[0].hasOutstandingBalance,
            "Customer should still have outstanding balance")

        // Payment fields should be cleared
        XCTAssertEqual(vm.paymentAmount, "", "Payment amount should be cleared")
        XCTAssertEqual(vm.paymentNotes, "", "Payment notes should be cleared")
    }

    func testRecordPayment_EmptyAmount_FailsValidation() async {
        let mockAPI = MockAPIClient()
        let vm = CustomerViewModel(apiClient: mockAPI)

        vm.paymentAmount = ""

        let success = await vm.recordPayment(customerId: 1)

        XCTAssertFalse(success)
        XCTAssertEqual(vm.errorMessage, "Payment amount is required.")
    }

    func testCreateCustomer_EmptyName_FailsValidation() async {
        let mockAPI = MockAPIClient()
        let vm = CustomerViewModel(apiClient: mockAPI)

        vm.newCustomerName = ""

        let customer = await vm.createCustomer()

        XCTAssertNil(customer)
        XCTAssertEqual(vm.errorMessage, "Customer name is required.")
    }

    func testCreateCustomer_Success_AddsToList() async {
        let mockAPI = MockAPIClient()
        let vm = CustomerViewModel(apiClient: mockAPI)

        mockAPI.postHandler = { _, _ in
            let response: APIResponse<Customer> = APIResponse(
                success: true,
                data: Customer(
                    id: 5, storeId: 1, name: "Priya Sharma",
                    mobile: "9876543212", email: "priya@example.com",
                    isDefault: 0, outstandingBalance: "0.00",
                    createdAt: "2026-03-01 16:00:00"
                ),
                meta: nil,
                error: nil
            )
            return response
        }

        vm.newCustomerName = "Priya Sharma"
        vm.newCustomerMobile = "9876543212"
        vm.newCustomerEmail = "priya@example.com"

        let customer = await vm.createCustomer()

        XCTAssertNotNil(customer)
        XCTAssertEqual(customer?.name, "Priya Sharma")
        XCTAssertEqual(vm.customers.count, 1, "Customer should be added to list")
        XCTAssertEqual(vm.customers[0].id, 5)
        XCTAssertEqual(vm.newCustomerName, "", "Form fields should be cleared")
        XCTAssertEqual(vm.newCustomerMobile, "")
        XCTAssertEqual(vm.newCustomerEmail, "")
        XCTAssertFalse(vm.showCreateForm, "Form should be dismissed")
    }

    func testFilteredCustomers_SearchByName() {
        let mockAPI = MockAPIClient()
        let vm = CustomerViewModel(apiClient: mockAPI)

        vm.customers = [
            Customer(id: 1, storeId: 1, name: "Ramesh Kumar", mobile: "9876543210",
                     email: nil, isDefault: 0, outstandingBalance: "0.00", createdAt: nil),
            Customer(id: 2, storeId: 1, name: "Suresh Patel", mobile: "9876543211",
                     email: nil, isDefault: 0, outstandingBalance: "0.00", createdAt: nil),
            Customer(id: 3, storeId: 1, name: "Priya Sharma", mobile: "9876543212",
                     email: nil, isDefault: 0, outstandingBalance: "0.00", createdAt: nil),
        ]

        vm.searchText = "ramesh"
        XCTAssertEqual(vm.filteredCustomers.count, 1)
        XCTAssertEqual(vm.filteredCustomers[0].name, "Ramesh Kumar")

        vm.searchText = ""
        XCTAssertEqual(vm.filteredCustomers.count, 3, "Empty search shows all customers")
    }

    func testFilteredCustomers_SearchByMobile() {
        let mockAPI = MockAPIClient()
        let vm = CustomerViewModel(apiClient: mockAPI)

        vm.customers = [
            Customer(id: 1, storeId: 1, name: "Ramesh Kumar", mobile: "9876543210",
                     email: nil, isDefault: 0, outstandingBalance: "0.00", createdAt: nil),
            Customer(id: 2, storeId: 1, name: "Suresh Patel", mobile: "9999888877",
                     email: nil, isDefault: 0, outstandingBalance: "0.00", createdAt: nil),
        ]

        vm.searchText = "9999"
        XCTAssertEqual(vm.filteredCustomers.count, 1)
        XCTAssertEqual(vm.filteredCustomers[0].name, "Suresh Patel")
    }
}

// MARK: - Model Decoding Tests

@MainActor
final class ModelDecodingTests: XCTestCase {

    func testAPIResponseEnvelope_DecodesCorrectly() {
        let json = """
        {
            "success": true,
            "data": { "access_token": "abc", "refresh_token": "xyz", "expires_in": 900 },
            "meta": null,
            "error": null
        }
        """
        let response: APIResponse<LoginResponse> = decodeJSON(json)
        XCTAssertTrue(response.success)
        XCTAssertEqual(response.data?.accessToken, "abc")
        XCTAssertEqual(response.data?.refreshToken, "xyz")
        XCTAssertEqual(response.data?.expiresIn, 900)
        XCTAssertNil(response.error)
    }

    func testAPIResponseEnvelope_ErrorResponse() {
        let json = """
        {
            "success": false,
            "data": null,
            "meta": null,
            "error": "Invalid email or password."
        }
        """
        let response: APIResponse<LoginResponse> = decodeJSON(json)
        XCTAssertFalse(response.success)
        XCTAssertNil(response.data)
        XCTAssertEqual(response.error, "Invalid email or password.")
    }

    func testMeta_DecodesPagination() {
        let json = """
        {
            "success": true,
            "data": [],
            "meta": { "page": 2, "per_page": 20, "total": 48 },
            "error": null
        }
        """
        let response: APIResponse<[Product]> = decodeJSON(json)
        XCTAssertEqual(response.meta?.page, 2)
        XCTAssertEqual(response.meta?.perPage, 20)
        XCTAssertEqual(response.meta?.total, 48)
    }

    func testSalesTrendResponse_DecodesCorrectly() {
        let json = """
        {
            "labels": ["Mon", "Tue", "Wed"],
            "data": [12500.0, 15200.0, 8900.0]
        }
        """
        let response: SalesTrendResponse = decodeJSON(json)
        XCTAssertEqual(response.labels.count, 3)
        XCTAssertEqual(response.data.count, 3)
        XCTAssertEqual(response.labels[0], "Mon")
        XCTAssertEqual(response.data[1], 15200.0)
    }
}
