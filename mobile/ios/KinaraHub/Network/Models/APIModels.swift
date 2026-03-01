import Foundation

// MARK: - Standard API Response Envelope

struct APIResponse<T: Codable>: Codable {
    let success: Bool
    let data: T?
    let meta: Meta?
    let error: String?
}

struct Meta: Codable {
    let page: Int
    let perPage: Int
    let total: Int
    let totalPages: Int?

    enum CodingKeys: String, CodingKey {
        case page
        case perPage = "per_page"
        case total
        case totalPages = "total_pages"
    }
}

// MARK: - Auth

struct LoginRequest: Codable {
    let email: String
    let password: String
}

struct LoginResponse: Codable {
    let accessToken: String
    let refreshToken: String
    let tokenType: String?
    let expiresIn: Int
    let user: LoginUser?

    enum CodingKeys: String, CodingKey {
        case accessToken = "access_token"
        case refreshToken = "refresh_token"
        case tokenType = "token_type"
        case expiresIn = "expires_in"
        case user
    }
}

struct LoginUser: Codable {
    let id: Int
    let name: String
    let email: String
    let storeId: Int
    let storeName: String
    let roleId: Int

    enum CodingKeys: String, CodingKey {
        case id, name, email
        case storeId = "store_id"
        case storeName = "store_name"
        case roleId = "role_id"
    }
}

struct RefreshRequest: Codable {
    let refreshToken: String

    enum CodingKeys: String, CodingKey {
        case refreshToken = "refresh_token"
    }
}

struct RefreshResponse: Codable {
    let accessToken: String
    let refreshToken: String
    let tokenType: String?
    let expiresIn: Int

    enum CodingKeys: String, CodingKey {
        case accessToken = "access_token"
        case refreshToken = "refresh_token"
        case tokenType = "token_type"
        case expiresIn = "expires_in"
    }
}

struct LogoutRequest: Codable {
    let refreshToken: String

    enum CodingKeys: String, CodingKey {
        case refreshToken = "refresh_token"
    }
}

// MARK: - User / Staff

struct User: Codable, Identifiable {
    let id: Int
    let storeId: Int
    let name: String
    let email: String
    let mobile: String?
    let roleId: Int
    let roleName: String?
    let storeName: String?

    enum CodingKeys: String, CodingKey {
        case id
        case storeId = "store_id"
        case name, email, mobile
        case roleId = "role_id"
        case roleName = "role_name"
        case storeName = "store_name"
    }
}

// MARK: - Product / Inventory

struct Product: Codable, Identifiable, Hashable {
    let id: Int
    let storeId: Int?
    let sku: String
    let name: String
    let categoryId: Int?
    let categoryName: String?
    let uomId: Int?
    let uomName: String?
    let uomAbbr: String?
    let sellingPrice: String
    let costPrice: String?
    let stockQuantity: String
    let reorderPoint: String
    let status: String
    let version: Int?
    let variants: [ProductVariant]?
    let createdAt: String?
    let updatedAt: String?

    enum CodingKeys: String, CodingKey {
        case id
        case storeId = "store_id"
        case sku, name
        case categoryId = "category_id"
        case categoryName = "category_name"
        case uomId = "uom_id"
        case uomName = "uom_name"
        case uomAbbr = "uom_abbr"
        case sellingPrice = "selling_price"
        case costPrice = "cost_price"
        case stockQuantity = "stock_quantity"
        case reorderPoint = "reorder_point"
        case status, version, variants
        case createdAt = "created_at"
        case updatedAt = "updated_at"
    }

    var stockStatus: StockStatus {
        let qty = Decimal(string: stockQuantity) ?? 0
        let reorder = Decimal(string: reorderPoint) ?? 0
        if qty == 0 {
            return .outOfStock
        } else if qty <= reorder {
            return .lowStock
        } else {
            return .inStock
        }
    }

    var sellingPriceDecimal: Decimal {
        Decimal(string: sellingPrice) ?? 0
    }

    var costPriceDecimal: Decimal? {
        guard let cp = costPrice else { return nil }
        return Decimal(string: cp)
    }

    var stockQuantityDecimal: Decimal {
        Decimal(string: stockQuantity) ?? 0
    }

    static func == (lhs: Product, rhs: Product) -> Bool {
        lhs.id == rhs.id
    }

    func hash(into hasher: inout Hasher) {
        hasher.combine(id)
    }
}

struct ProductVariant: Codable, Identifiable {
    let id: Int
    let productId: Int?
    let variantName: String
    let sku: String
    let sellingPrice: String
    let costPrice: String?
    let stockQuantity: String
    let reorderPoint: String
    let version: Int?

    enum CodingKeys: String, CodingKey {
        case id
        case productId = "product_id"
        case variantName = "variant_name"
        case sku
        case sellingPrice = "selling_price"
        case costPrice = "cost_price"
        case stockQuantity = "stock_quantity"
        case reorderPoint = "reorder_point"
        case version
    }

    var stockStatus: StockStatus {
        let qty = Decimal(string: stockQuantity) ?? 0
        let reorder = Decimal(string: reorderPoint) ?? 0
        if qty == 0 {
            return .outOfStock
        } else if qty <= reorder {
            return .lowStock
        } else {
            return .inStock
        }
    }
}

enum StockStatus: String, Codable {
    case inStock = "in_stock"
    case lowStock = "low_stock"
    case outOfStock = "out_of_stock"
}

// MARK: - Category

struct Category: Codable, Identifiable {
    let id: Int
    let name: String
}

// MARK: - Sales

struct Sale: Codable, Identifiable {
    let id: Int
    let storeId: Int?
    let saleNumber: String
    let saleDate: String
    let entryMode: String
    let customerId: Int?
    let customerName: String?
    let paymentMethod: String
    let subtotal: String
    let taxAmount: String
    let totalAmount: String
    let notes: String?
    let createdBy: Int?
    let createdByName: String?
    let items: [SaleItem]?
    let createdAt: String?

    enum CodingKeys: String, CodingKey {
        case id
        case storeId = "store_id"
        case saleNumber = "sale_number"
        case saleDate = "sale_date"
        case entryMode = "entry_mode"
        case customerId = "customer_id"
        case customerName = "customer_name"
        case paymentMethod = "payment_method"
        case subtotal
        case taxAmount = "tax_amount"
        case totalAmount = "total_amount"
        case notes
        case createdBy = "created_by"
        case createdByName = "created_by_name"
        case items
        case createdAt = "created_at"
    }

    var totalAmountDecimal: Decimal {
        Decimal(string: totalAmount) ?? 0
    }
}

struct SaleItem: Codable, Identifiable {
    let id: Int
    let productId: Int?
    let variantId: Int?
    let productNameSnapshot: String
    let skuSnapshot: String
    let quantity: String
    let unitPrice: String
    let costPriceSnapshot: String?
    let lineTotal: String

    enum CodingKeys: String, CodingKey {
        case id
        case productId = "product_id"
        case variantId = "variant_id"
        case productNameSnapshot = "product_name_snapshot"
        case skuSnapshot = "sku_snapshot"
        case quantity
        case unitPrice = "unit_price"
        case costPriceSnapshot = "cost_price_snapshot"
        case lineTotal = "line_total"
    }
}

struct CreateSaleRequest: Codable {
    let entryMode: String
    let saleDate: String?
    let customerId: Int?
    let paymentMethod: String
    let notes: String?
    let items: [CreateSaleItemRequest]

    enum CodingKeys: String, CodingKey {
        case entryMode = "entry_mode"
        case saleDate = "sale_date"
        case customerId = "customer_id"
        case paymentMethod = "payment_method"
        case notes, items
    }
}

struct CreateSaleItemRequest: Codable {
    let productId: Int
    let variantId: Int?
    let quantity: String
    let unitPrice: String

    enum CodingKeys: String, CodingKey {
        case productId = "product_id"
        case variantId = "variant_id"
        case quantity
        case unitPrice = "unit_price"
    }
}

/// POST /sales returns {sale_id: X}, not a full Sale
struct CreateSaleResponse: Codable {
    let saleId: Int

    enum CodingKeys: String, CodingKey {
        case saleId = "sale_id"
    }
}

enum PaymentMethod: String, CaseIterable, Identifiable {
    case cash
    case upi
    case card
    case credit

    var id: String { rawValue }

    var displayName: String {
        switch self {
        case .cash: return "Cash"
        case .upi: return "UPI"
        case .card: return "Card"
        case .credit: return "Credit"
        }
    }
}

// MARK: - Customer

struct Customer: Codable, Identifiable {
    let id: Int
    let storeId: Int?
    let name: String
    let mobile: String?
    let email: String?
    let isDefault: Int?
    let outstandingBalance: String
    let createdAt: String?

    enum CodingKeys: String, CodingKey {
        case id
        case storeId = "store_id"
        case name, mobile, email
        case isDefault = "is_default"
        case outstandingBalance = "outstanding_balance"
        case createdAt = "created_at"
    }

    var outstandingBalanceDecimal: Decimal {
        Decimal(string: outstandingBalance) ?? 0
    }

    var hasOutstandingBalance: Bool {
        outstandingBalanceDecimal > 0
    }
}

struct CreateCustomerRequest: Codable {
    let name: String
    let mobile: String?
    let email: String?
}

struct CustomerCredit: Codable, Identifiable {
    let id: Int
    let saleId: Int?
    let saleNumber: String?
    let amountDue: String
    let amountPaid: String
    let balance: String
    let dueDate: String?
    let createdAt: String?

    enum CodingKeys: String, CodingKey {
        case id
        case saleId = "sale_id"
        case saleNumber = "sale_number"
        case amountDue = "amount_due"
        case amountPaid = "amount_paid"
        case balance
        case dueDate = "due_date"
        case createdAt = "created_at"
    }
}

struct RecordPaymentRequest: Codable {
    let amount: String
    let paymentMethod: String
    let notes: String?

    enum CodingKeys: String, CodingKey {
        case amount
        case paymentMethod = "payment_method"
        case notes
    }
}

/// POST /customers returns {customer_id: X}, not a full Customer
struct CreateCustomerResponse: Codable {
    let customerId: Int

    enum CodingKeys: String, CodingKey {
        case customerId = "customer_id"
    }
}

/// GET /customers/:id/credits returns nested object
struct CustomerCreditsResponse: Codable {
    let customer: Customer
    let credits: [CustomerCredit]
    let paymentHistory: [CustomerPayment]

    enum CodingKeys: String, CodingKey {
        case customer, credits
        case paymentHistory = "payment_history"
    }
}

struct CustomerPayment: Codable, Identifiable {
    let id: Int
    let amount: String
    let paymentMethod: String
    let notes: String?
    let createdAt: String?

    enum CodingKeys: String, CodingKey {
        case id, amount, notes
        case paymentMethod = "payment_method"
        case createdAt = "created_at"
    }
}

// MARK: - Dashboard

/// Matches the shape returned by DashboardService::getAllStats()
struct DashboardSummary: Codable {
    let todayRevenue: Double
    let yesterdayRevenue: Double
    let percentChange: Double
    let weekRevenue: Double
    let monthRevenue: Double
    let stockValue: Double
    let outOfStock: Int
    let lowStock: Int
    let topProducts: [TopProduct]
    let recentSales: [RecentSale]
    let salesTrend: SalesTrendResponse?
    let paymentBreakdown: PaymentBreakdownResponse?
    let stockDistribution: StockDistributionResponse?

    enum CodingKeys: String, CodingKey {
        case todayRevenue = "today_revenue"
        case yesterdayRevenue = "yesterday_revenue"
        case percentChange = "percent_change"
        case weekRevenue = "week_revenue"
        case monthRevenue = "month_revenue"
        case stockValue = "stock_value"
        case outOfStock = "out_of_stock"
        case lowStock = "low_stock"
        case topProducts = "top_products"
        case recentSales = "recent_sales"
        case salesTrend = "sales_trend"
        case paymentBreakdown = "payment_breakdown"
        case stockDistribution = "stock_distribution"
    }
}

/// Top product from dashboard — returned by top5ProductsToday()
struct TopProduct: Codable, Identifiable {
    var id: String { productName }
    let productName: String
    let unitsSold: Double
    let revenue: Double

    enum CodingKeys: String, CodingKey {
        case productName = "product_name"
        case unitsSold = "units_sold"
        case revenue
    }
}

/// Recent sale from dashboard — lighter than full Sale model
struct RecentSale: Codable, Identifiable {
    var id: String { saleNumber }
    let saleNumber: String
    let saleDate: String
    let paymentMethod: String
    let totalAmount: Double
    let customerName: String?

    enum CodingKeys: String, CodingKey {
        case saleNumber = "sale_number"
        case saleDate = "sale_date"
        case paymentMethod = "payment_method"
        case totalAmount = "total_amount"
        case customerName = "customer_name"
    }
}

struct PaymentBreakdownResponse: Codable {
    let labels: [String]
    let amounts: [Double]
}

struct StockDistributionResponse: Codable {
    let labels: [String]
    let counts: [Int]
}

// MARK: - Sales Trend Chart

struct ChartDataPoint: Identifiable, Equatable {
    let id = UUID()
    let label: String
    let amount: Double

    static func == (lhs: ChartDataPoint, rhs: ChartDataPoint) -> Bool {
        lhs.label == rhs.label && lhs.amount == rhs.amount
    }
}

struct SalesTrendResponse: Codable {
    let labels: [String]
    let amounts: [Double]
}

enum TrendPeriod: String, CaseIterable, Identifiable {
    case day
    case week
    case month
    case year

    var id: String { rawValue }

    var displayName: String {
        switch self {
        case .day: return "Day"
        case .week: return "Week"
        case .month: return "Month"
        case .year: return "Year"
        }
    }
}
