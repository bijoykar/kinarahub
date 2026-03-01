import Foundation

@MainActor
final class SalesViewModel: ObservableObject {
    // MARK: - Sales History
    @Published var sales: [Sale] = []
    @Published var isLoading = false
    @Published var errorMessage: String?
    @Published var currentPage = 1
    @Published var totalPages = 1
    @Published var dateFrom: Date?
    @Published var dateTo: Date?

    // MARK: - POS Cart
    @Published var cartItems: [CartItem] = []
    @Published var selectedPaymentMethod: PaymentMethod = .cash
    @Published var selectedCustomer: Customer?
    @Published var saleNotes: String = ""
    @Published var isSubmitting = false
    @Published var lastCreatedSale: Sale?
    @Published var showSaleSuccess = false
    @Published var validationError: String?

    // MARK: - Product Search for POS
    @Published var searchResults: [Product] = []
    @Published var posSearchText = ""

    private let apiClient: APIClientProtocol

    init(apiClient: APIClientProtocol? = nil) {
        self.apiClient = apiClient ?? APIClient.shared
    }

    var cartSubtotal: Decimal {
        cartItems.reduce(Decimal.zero) { $0 + $1.lineTotal }
    }

    var cartTotal: Decimal {
        cartSubtotal
    }

    var isCartEmpty: Bool {
        cartItems.isEmpty
    }

    // MARK: - Sales History

    func loadSales(page: Int = 1) async {
        isLoading = true
        errorMessage = nil

        var params: [String: String] = [
            "page": "\(page)",
            "per_page": "\(AppConfig.defaultPageSize)"
        ]

        let formatter = DateFormatter()
        formatter.dateFormat = "yyyy-MM-dd"

        if let from = dateFrom {
            params["from"] = formatter.string(from: from)
        }
        if let to = dateTo {
            params["to"] = formatter.string(from: to)
        }

        do {
            let response: APIResponse<[Sale]> = try await apiClient.get(
                url: APIEndpoints.Sales.list,
                queryParams: params
            )
            if page == 1 {
                sales = response.data ?? []
            } else {
                sales.append(contentsOf: response.data ?? [])
            }
            if let meta = response.meta {
                currentPage = meta.page
                totalPages = meta.total > 0
                    ? Int(ceil(Double(meta.total) / Double(meta.perPage)))
                    : 1
            }
        } catch let error as APIError {
            errorMessage = error.errorDescription
        } catch {
            errorMessage = error.localizedDescription
        }

        isLoading = false
    }

    func loadNextPage() async {
        guard currentPage < totalPages, !isLoading else { return }
        await loadSales(page: currentPage + 1)
    }

    func refreshSales() async {
        currentPage = 1
        await loadSales(page: 1)
    }

    // MARK: - POS

    func searchProducts(query: String) async {
        guard !query.isEmpty else {
            searchResults = []
            return
        }

        do {
            let response: APIResponse<[Product]> = try await apiClient.get(
                url: APIEndpoints.Products.list,
                queryParams: ["search": query, "per_page": "10"]
            )
            searchResults = response.data ?? []
        } catch {
            searchResults = []
        }
    }

    func addToCart(product: Product, quantity: Decimal = 1) {
        if let index = cartItems.firstIndex(where: { $0.productId == product.id && $0.variantId == nil }) {
            cartItems[index].quantity += quantity
        } else {
            let item = CartItem(
                productId: product.id,
                variantId: nil,
                name: product.name,
                sku: product.sku,
                unitPrice: product.sellingPriceDecimal,
                quantity: quantity,
                maxStock: product.stockQuantityDecimal
            )
            cartItems.append(item)
        }
    }

    func addVariantToCart(product: Product, variant: ProductVariant, quantity: Decimal = 1) {
        if let index = cartItems.firstIndex(where: { $0.productId == product.id && $0.variantId == variant.id }) {
            cartItems[index].quantity += quantity
        } else {
            let item = CartItem(
                productId: product.id,
                variantId: variant.id,
                name: "\(product.name) - \(variant.variantName)",
                sku: variant.sku,
                unitPrice: Decimal(string: variant.sellingPrice) ?? 0,
                quantity: quantity,
                maxStock: Decimal(string: variant.stockQuantity) ?? 0
            )
            cartItems.append(item)
        }
    }

    func updateCartItemQuantity(at index: Int, quantity: Decimal) {
        guard cartItems.indices.contains(index) else { return }
        if quantity <= 0 {
            cartItems.remove(at: index)
        } else {
            cartItems[index].quantity = quantity
        }
    }

    func removeFromCart(at index: Int) {
        guard cartItems.indices.contains(index) else { return }
        cartItems.remove(at: index)
    }

    func clearCart() {
        cartItems = []
        selectedPaymentMethod = .cash
        selectedCustomer = nil
        saleNotes = ""
        posSearchText = ""
        searchResults = []
        validationError = nil
    }

    func submitSale() async {
        validationError = nil

        guard !cartItems.isEmpty else {
            validationError = "Cart is empty. Add items to continue."
            return
        }

        if selectedPaymentMethod == .credit && selectedCustomer == nil {
            validationError = "Customer required for credit sales."
            return
        }

        isSubmitting = true

        let items = cartItems.map { item in
            CreateSaleItemRequest(
                productId: item.productId,
                variantId: item.variantId,
                quantity: "\(item.quantity)",
                unitPrice: "\(item.unitPrice)"
            )
        }

        let body = CreateSaleRequest(
            entryMode: "pos",
            saleDate: nil,
            customerId: selectedCustomer?.id,
            paymentMethod: selectedPaymentMethod.rawValue,
            notes: saleNotes.isEmpty ? nil : saleNotes,
            items: items
        )

        do {
            let response: APIResponse<Sale> = try await apiClient.post(
                url: APIEndpoints.Sales.create,
                body: body
            )
            lastCreatedSale = response.data
            showSaleSuccess = true
            clearCart()
        } catch let error as APIError {
            if case .conflict = error {
                validationError = "Stock was modified. Please refresh and try again."
            } else {
                validationError = error.errorDescription
            }
        } catch {
            validationError = error.localizedDescription
        }

        isSubmitting = false
    }
}

// MARK: - Cart Item

struct CartItem: Identifiable {
    let id = UUID()
    let productId: Int
    let variantId: Int?
    let name: String
    let sku: String
    let unitPrice: Decimal
    var quantity: Decimal
    let maxStock: Decimal

    var lineTotal: Decimal {
        unitPrice * quantity
    }
}
