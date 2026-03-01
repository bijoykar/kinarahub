import Foundation

enum APIEndpoints {
    static let baseURL = AppConfig.apiBaseURL

    // MARK: - Auth
    enum Auth {
        static let login = "\(baseURL)/auth/login"
        static let refresh = "\(baseURL)/auth/refresh"
        static let logout = "\(baseURL)/auth/logout"
    }

    // MARK: - Products
    enum Products {
        static let list = "\(baseURL)/products"

        static func detail(_ id: Int) -> String {
            "\(baseURL)/products/\(id)"
        }

        static func variants(_ id: Int) -> String {
            "\(baseURL)/products/\(id)/variants"
        }
    }

    // MARK: - Sales
    enum Sales {
        static let list = "\(baseURL)/sales"
        static let create = "\(baseURL)/sales"

        static func detail(_ id: Int) -> String {
            "\(baseURL)/sales/\(id)"
        }
    }

    // MARK: - Customers
    enum Customers {
        static let list = "\(baseURL)/customers"
        static let create = "\(baseURL)/customers"

        static func credits(_ id: Int) -> String {
            "\(baseURL)/customers/\(id)/credits"
        }

        static func payments(_ id: Int) -> String {
            "\(baseURL)/customers/\(id)/payments"
        }
    }

    // MARK: - Dashboard
    enum Dashboard {
        static let summary = "\(baseURL)/dashboard/summary"
    }
}
