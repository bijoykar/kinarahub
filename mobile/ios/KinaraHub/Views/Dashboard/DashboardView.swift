import SwiftUI
import Charts

struct DashboardView: View {
    @StateObject private var viewModel = DashboardViewModel()
    @EnvironmentObject var authViewModel: AuthViewModel

    var body: some View {
        ScrollView {
            if viewModel.isLoading && viewModel.summary == nil {
                VStack(spacing: 16) {
                    ProgressView()
                    Text("Loading dashboard...")
                        .foregroundStyle(.secondary)
                }
                .frame(maxWidth: .infinity, maxHeight: .infinity)
                .padding(.top, 100)
            } else if let error = viewModel.errorMessage, viewModel.summary == nil {
                ErrorStateView(message: error) {
                    Task { await viewModel.loadSummary() }
                }
                .padding(.top, 100)
            } else if let summary = viewModel.summary {
                VStack(spacing: 20) {
                    // KPI Cards
                    kpiSection(summary)

                    // Stock Alerts
                    stockAlertSection(summary)

                    // Sales Trend Chart
                    SalesTrendChart(
                        dataPoints: viewModel.trendDataPoints,
                        selectedPeriod: $viewModel.selectedPeriod,
                        onPeriodChange: { period in
                            Task { await viewModel.loadSalesTrend(period: period) }
                        },
                        isLoading: viewModel.isTrendLoading
                    )

                    // Top Products Today
                    if !summary.topProducts.isEmpty {
                        topProductsSection(summary.topProducts)
                    }

                    // Recent Sales
                    if !summary.recentSales.isEmpty {
                        recentSalesSection(summary.recentSales)
                    }
                }
                .padding()
            }
        }
        .navigationTitle("Dashboard")
        .toolbar {
            ToolbarItem(placement: .navigationBarTrailing) {
                Menu {
                    Button(role: .destructive) {
                        Task { await authViewModel.logout() }
                    } label: {
                        Label("Sign Out", systemImage: "rectangle.portrait.and.arrow.right")
                    }
                } label: {
                    Image(systemName: "person.circle.fill")
                        .font(.title3)
                }
            }
        }
        .refreshable {
            await viewModel.loadSummary()
            await viewModel.loadSalesTrend(period: viewModel.selectedPeriod)
        }
        .task {
            if viewModel.summary == nil {
                await viewModel.loadSummary()
                await viewModel.loadSalesTrend(period: viewModel.selectedPeriod)
            }
        }
    }

    // MARK: - KPI Cards

    @ViewBuilder
    private func kpiSection(_ summary: DashboardSummary) -> some View {
        LazyVGrid(columns: [
            GridItem(.flexible()),
            GridItem(.flexible())
        ], spacing: 12) {
            KPICard(
                title: "Sales Today",
                value: formatCurrency(summary.todayRevenue),
                changePercent: summary.percentChange,
                icon: "indianrupeesign.circle.fill",
                color: .indigo
            )

            KPICard(
                title: "This Week",
                value: formatCurrency(summary.weekRevenue),
                changePercent: nil,
                icon: "calendar",
                color: .blue
            )

            KPICard(
                title: "This Month",
                value: formatCurrency(summary.monthRevenue),
                changePercent: nil,
                icon: "calendar.badge.clock",
                color: .teal
            )

            KPICard(
                title: "Stock Value",
                value: formatCurrency(summary.stockValue),
                changePercent: nil,
                icon: "cube.box.fill",
                color: .orange
            )
        }
    }

    // MARK: - Stock Alerts

    @ViewBuilder
    private func stockAlertSection(_ summary: DashboardSummary) -> some View {
        HStack(spacing: 12) {
            StockAlertCard(
                title: "Out of Stock",
                count: summary.outOfStock,
                color: .red,
                icon: "exclamationmark.triangle.fill"
            )

            StockAlertCard(
                title: "Low Stock",
                count: summary.lowStock,
                color: .orange,
                icon: "exclamationmark.circle.fill"
            )
        }
    }

    // MARK: - Top Products

    @ViewBuilder
    private func topProductsSection(_ products: [TopProduct]) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Top Products Today")
                .font(.headline)

            VStack(spacing: 0) {
                ForEach(Array(products.enumerated()), id: \.element.productName) { index, product in
                    HStack {
                        Text("\(index + 1).")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                            .frame(width: 24)

                        Text(product.productName)
                            .font(.subheadline)
                            .lineLimit(1)

                        Spacer()

                        VStack(alignment: .trailing) {
                            Text("\(Int(product.unitsSold)) sold")
                                .font(.caption)
                                .foregroundStyle(.secondary)
                            Text(formatCurrency(product.revenue))
                                .font(.caption)
                                .fontWeight(.medium)
                        }
                    }
                    .padding(.vertical, 8)
                    .padding(.horizontal, 12)

                    if index < products.count - 1 {
                        Divider()
                    }
                }
            }
            .background(Color(.systemGray6))
            .cornerRadius(12)
        }
    }

    // MARK: - Recent Sales

    @ViewBuilder
    private func recentSalesSection(_ sales: [RecentSale]) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Recent Sales")
                .font(.headline)

            VStack(spacing: 0) {
                ForEach(sales) { sale in
                    HStack {
                        VStack(alignment: .leading, spacing: 2) {
                            Text(sale.saleNumber)
                                .font(.subheadline)
                                .fontWeight(.medium)
                            Text(sale.saleDate)
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        }

                        Spacer()

                        VStack(alignment: .trailing, spacing: 2) {
                            Text(formatCurrency(sale.totalAmount))
                                .font(.subheadline)
                                .fontWeight(.medium)
                            PaymentBadge(method: sale.paymentMethod)
                        }
                    }
                    .padding(.vertical, 8)
                    .padding(.horizontal, 12)

                    if sale.id != sales.last?.id {
                        Divider()
                    }
                }
            }
            .background(Color(.systemGray6))
            .cornerRadius(12)
        }
    }

    // MARK: - Helpers

    private func formatCurrency(_ value: Double) -> String {
        let formatted = value.truncatingRemainder(dividingBy: 1) == 0
            ? String(format: "%.0f", value)
            : String(format: "%.2f", value)
        return "\(AppConfig.currencySymbol)\(formatted)"
    }
}

// MARK: - KPI Card

struct KPICard: View {
    let title: String
    let value: String
    let changePercent: Double?
    let icon: String
    let color: Color

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack {
                Image(systemName: icon)
                    .foregroundStyle(color)
                    .font(.title3)
                Spacer()
                if let change = changePercent {
                    HStack(spacing: 2) {
                        Image(systemName: change >= 0 ? "arrow.up.right" : "arrow.down.right")
                            .font(.caption2)
                        Text(String(format: "%.1f%%", abs(change)))
                            .font(.caption2)
                    }
                    .foregroundStyle(change >= 0 ? .green : .red)
                }
            }

            Text(value)
                .font(.title3)
                .fontWeight(.bold)
                .lineLimit(1)
                .minimumScaleFactor(0.7)

            Text(title)
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .padding()
        .background(Color(.systemGray6))
        .cornerRadius(12)
    }
}

// MARK: - Stock Alert Card

struct StockAlertCard: View {
    let title: String
    let count: Int
    let color: Color
    let icon: String

    var body: some View {
        HStack(spacing: 12) {
            Image(systemName: icon)
                .font(.title2)
                .foregroundStyle(color)

            VStack(alignment: .leading) {
                Text("\(count)")
                    .font(.title2)
                    .fontWeight(.bold)
                Text(title)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            Spacer()
        }
        .padding()
        .frame(maxWidth: .infinity)
        .background(color.opacity(0.1))
        .cornerRadius(12)
    }
}

// MARK: - Payment Badge

struct PaymentBadge: View {
    let method: String

    var color: Color {
        switch method.lowercased() {
        case "cash": return .green
        case "upi": return .blue
        case "card": return .purple
        case "credit": return .orange
        default: return .gray
        }
    }

    var body: some View {
        Text(method.capitalized)
            .font(.caption2)
            .fontWeight(.medium)
            .padding(.horizontal, 8)
            .padding(.vertical, 3)
            .background(color.opacity(0.15))
            .foregroundStyle(color)
            .cornerRadius(6)
    }
}
