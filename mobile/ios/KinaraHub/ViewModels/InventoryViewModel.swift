import Foundation

@MainActor
final class InventoryViewModel: ObservableObject {
    @Published var products: [Product] = []
    @Published var isLoading = false
    @Published var errorMessage: String?
    @Published var searchText = ""
    @Published var selectedCategory: String?
    @Published var currentPage = 1
    @Published var totalPages = 1
    @Published var totalProducts = 0

    private let apiClient: APIClientProtocol

    init(apiClient: APIClientProtocol? = nil) {
        self.apiClient = apiClient ?? APIClient.shared
    }

    var filteredProducts: [Product] {
        if searchText.isEmpty {
            return products
        }
        let query = searchText.lowercased()
        return products.filter {
            $0.name.lowercased().contains(query) ||
            $0.sku.lowercased().contains(query)
        }
    }

    func loadProducts(page: Int = 1) async {
        isLoading = true
        errorMessage = nil

        var params: [String: String] = [
            "page": "\(page)",
            "per_page": "\(AppConfig.defaultPageSize)"
        ]

        if let category = selectedCategory, !category.isEmpty {
            params["category"] = category
        }

        if !searchText.isEmpty {
            params["search"] = searchText
        }

        do {
            let response: APIResponse<[Product]> = try await apiClient.get(
                url: APIEndpoints.Products.list,
                queryParams: params
            )
            if page == 1 {
                products = response.data ?? []
            } else {
                products.append(contentsOf: response.data ?? [])
            }
            if let meta = response.meta {
                currentPage = meta.page
                totalProducts = meta.total
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
        await loadProducts(page: currentPage + 1)
    }

    func refresh() async {
        currentPage = 1
        await loadProducts(page: 1)
    }

    func loadProductDetail(id: Int) async -> Product? {
        do {
            let response: APIResponse<Product> = try await apiClient.get(
                url: APIEndpoints.Products.detail(id)
            )
            return response.data
        } catch {
            errorMessage = error.localizedDescription
            return nil
        }
    }
}
