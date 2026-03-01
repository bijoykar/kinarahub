import SwiftUI

struct InventoryListView: View {
    @StateObject private var viewModel = InventoryViewModel()

    var body: some View {
        Group {
            if viewModel.isLoading && viewModel.products.isEmpty {
                ProgressView("Loading inventory...")
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if let error = viewModel.errorMessage, viewModel.products.isEmpty {
                ErrorStateView(message: error) {
                    Task { await viewModel.refresh() }
                }
                .padding(.top, 80)
            } else if viewModel.filteredProducts.isEmpty {
                EmptyStateView(
                    icon: "cube.box",
                    title: "No Products",
                    message: viewModel.searchText.isEmpty
                        ? "Your inventory is empty."
                        : "No products match your search."
                )
                .padding(.top, 80)
            } else {
                productList
            }
        }
        .navigationTitle("Inventory")
        .searchable(text: $viewModel.searchText, prompt: "Search by name or SKU")
        .refreshable {
            await viewModel.refresh()
        }
        .task {
            if viewModel.products.isEmpty {
                await viewModel.loadProducts()
            }
        }
        .onChange(of: viewModel.searchText) { _ in
            Task {
                await viewModel.refresh()
            }
        }
    }

    private var productList: some View {
        List {
            ForEach(viewModel.filteredProducts) { product in
                NavigationLink(value: Route.productDetail(id: product.id)) {
                    ProductRow(product: product)
                }
            }

            if viewModel.currentPage < viewModel.totalPages {
                HStack {
                    Spacer()
                    ProgressView()
                        .task {
                            await viewModel.loadNextPage()
                        }
                    Spacer()
                }
                .listRowSeparator(.hidden)
            }
        }
        .listStyle(.plain)
    }
}

// MARK: - Product Row

struct ProductRow: View {
    let product: Product

    var body: some View {
        HStack(spacing: 12) {
            // Product icon
            RoundedRectangle(cornerRadius: 8)
                .fill(product.stockStatus.color.opacity(0.15))
                .frame(width: 44, height: 44)
                .overlay {
                    Image(systemName: "cube.box.fill")
                        .foregroundStyle(product.stockStatus.color)
                }

            VStack(alignment: .leading, spacing: 4) {
                Text(product.name)
                    .font(.subheadline)
                    .fontWeight(.medium)
                    .lineLimit(1)

                HStack(spacing: 8) {
                    Text(product.sku)
                        .font(.caption)
                        .foregroundStyle(.secondary)

                    if let category = product.categoryName {
                        Text(category)
                            .font(.caption2)
                            .padding(.horizontal, 6)
                            .padding(.vertical, 2)
                            .background(Color(.systemGray5))
                            .cornerRadius(4)
                    }
                }
            }

            Spacer()

            VStack(alignment: .trailing, spacing: 4) {
                Text("\(AppConfig.currencySymbol)\(product.sellingPriceDecimal)")
                    .font(.subheadline)
                    .fontWeight(.semibold)

                StockBadge(status: product.stockStatus)
            }
        }
        .padding(.vertical, 4)
    }
}

private extension StockStatus {
    var color: Color {
        switch self {
        case .inStock: return .green
        case .lowStock: return .orange
        case .outOfStock: return .red
        }
    }
}
