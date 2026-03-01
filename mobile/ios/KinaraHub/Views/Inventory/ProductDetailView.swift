import SwiftUI

struct ProductDetailView: View {
    let productId: Int
    @StateObject private var viewModel = InventoryViewModel()
    @State private var product: Product?
    @State private var isLoading = true

    var body: some View {
        ScrollView {
            if isLoading {
                ProgressView("Loading product...")
                    .padding(.top, 100)
            } else if let product {
                VStack(spacing: 20) {
                    productHeader(product)
                    priceSection(product)
                    stockSection(product)
                    if let variants = product.variants, !variants.isEmpty {
                        variantsSection(variants)
                    }
                    infoSection(product)
                }
                .padding()
            } else {
                ErrorStateView(message: "Product not found") {
                    Task { await loadProduct() }
                }
                .padding(.top, 100)
            }
        }
        .navigationTitle("Product Detail")
        .navigationBarTitleDisplayMode(.inline)
        .task {
            await loadProduct()
        }
    }

    private func loadProduct() async {
        isLoading = true
        product = await viewModel.loadProductDetail(id: productId)
        isLoading = false
    }

    // MARK: - Product Header

    @ViewBuilder
    private func productHeader(_ product: Product) -> some View {
        VStack(spacing: 12) {
            RoundedRectangle(cornerRadius: 16)
                .fill(Color(.systemGray6))
                .frame(height: 120)
                .overlay {
                    VStack(spacing: 8) {
                        Image(systemName: "cube.box.fill")
                            .font(.system(size: 36))
                            .foregroundStyle(.indigo)
                        Text(product.sku)
                            .font(.caption)
                            .fontWeight(.medium)
                            .foregroundStyle(.secondary)
                    }
                }

            Text(product.name)
                .font(.title2)
                .fontWeight(.bold)
                .multilineTextAlignment(.center)

            StockBadge(status: product.stockStatus)
        }
    }

    // MARK: - Price Section

    @ViewBuilder
    private func priceSection(_ product: Product) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Pricing")
                .font(.headline)

            HStack(spacing: 16) {
                DetailCard(
                    title: "Selling Price",
                    value: "\(AppConfig.currencySymbol)\(product.sellingPriceDecimal)",
                    color: .indigo
                )

                if let costPrice = product.costPriceDecimal {
                    DetailCard(
                        title: "Cost Price",
                        value: "\(AppConfig.currencySymbol)\(costPrice)",
                        color: .orange
                    )
                }
            }
        }
    }

    // MARK: - Stock Section

    @ViewBuilder
    private func stockSection(_ product: Product) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Stock")
                .font(.headline)

            HStack(spacing: 16) {
                DetailCard(
                    title: "Quantity",
                    value: "\(product.stockQuantityDecimal) \(product.uomAbbreviation ?? "")",
                    color: product.stockStatus == .outOfStock ? .red :
                           product.stockStatus == .lowStock ? .orange : .green
                )

                DetailCard(
                    title: "Reorder Point",
                    value: "\(Decimal(string: product.reorderPoint) ?? 0) \(product.uomAbbreviation ?? "")",
                    color: .gray
                )
            }
        }
    }

    // MARK: - Variants Section

    @ViewBuilder
    private func variantsSection(_ variants: [ProductVariant]) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Variants (\(variants.count))")
                .font(.headline)

            ForEach(variants) { variant in
                VStack(alignment: .leading, spacing: 6) {
                    HStack {
                        Text(variant.variantName)
                            .font(.subheadline)
                            .fontWeight(.medium)

                        Spacer()

                        StockBadge(status: variant.stockStatus)
                    }

                    HStack(spacing: 16) {
                        Label(variant.sku, systemImage: "barcode")
                            .font(.caption)
                            .foregroundStyle(.secondary)

                        Spacer()

                        Text("\(AppConfig.currencySymbol)\(Decimal(string: variant.sellingPrice) ?? 0)")
                            .font(.subheadline)
                            .fontWeight(.semibold)
                    }

                    Text("Stock: \(Decimal(string: variant.stockQuantity) ?? 0)")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                .padding()
                .background(Color(.systemGray6))
                .cornerRadius(10)
            }
        }
    }

    // MARK: - Info Section

    @ViewBuilder
    private func infoSection(_ product: Product) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Information")
                .font(.headline)

            VStack(spacing: 0) {
                if let category = product.categoryName {
                    InfoRow(label: "Category", value: category)
                    Divider()
                }
                if let uom = product.uomName {
                    InfoRow(label: "Unit of Measure", value: uom)
                    Divider()
                }
                InfoRow(label: "Status", value: product.status.capitalized)
                if let createdAt = product.createdAt {
                    Divider()
                    InfoRow(label: "Created", value: createdAt)
                }
            }
            .background(Color(.systemGray6))
            .cornerRadius(10)
        }
    }
}

// MARK: - Detail Card

struct DetailCard: View {
    let title: String
    let value: String
    let color: Color

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(title)
                .font(.caption)
                .foregroundStyle(.secondary)
            Text(value)
                .font(.title3)
                .fontWeight(.bold)
                .foregroundStyle(color)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(Color(.systemGray6))
        .cornerRadius(10)
    }
}

// MARK: - Info Row

struct InfoRow: View {
    let label: String
    let value: String

    var body: some View {
        HStack {
            Text(label)
                .font(.subheadline)
                .foregroundStyle(.secondary)
            Spacer()
            Text(value)
                .font(.subheadline)
                .fontWeight(.medium)
        }
        .padding(.horizontal, 12)
        .padding(.vertical, 10)
    }
}
