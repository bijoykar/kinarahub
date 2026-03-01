import SwiftUI

struct SalesHistoryView: View {
    @StateObject private var viewModel = SalesViewModel()
    @State private var showDateFilter = false

    var body: some View {
        Group {
            if viewModel.isLoading && viewModel.sales.isEmpty {
                ProgressView("Loading sales...")
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if let error = viewModel.errorMessage, viewModel.sales.isEmpty {
                ErrorStateView(message: error) {
                    Task { await viewModel.refreshSales() }
                }
                .padding(.top, 80)
            } else if viewModel.sales.isEmpty {
                EmptyStateView(
                    icon: "doc.text",
                    title: "No Sales",
                    message: "No sales recorded yet."
                )
                .padding(.top, 80)
            } else {
                salesList
            }
        }
        .navigationTitle("Sales History")
        .toolbar {
            ToolbarItem(placement: .navigationBarTrailing) {
                Button {
                    showDateFilter.toggle()
                } label: {
                    Image(systemName: "line.3.horizontal.decrease.circle")
                }
            }
        }
        .sheet(isPresented: $showDateFilter) {
            dateFilterSheet
        }
        .refreshable {
            await viewModel.refreshSales()
        }
        .task {
            if viewModel.sales.isEmpty {
                await viewModel.loadSales()
            }
        }
    }

    // MARK: - Sales List

    private var salesList: some View {
        List {
            ForEach(viewModel.sales) { sale in
                SaleRow(sale: sale)
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

    // MARK: - Date Filter Sheet

    private var dateFilterSheet: some View {
        NavigationStack {
            Form {
                Section("Date Range") {
                    DatePicker(
                        "From",
                        selection: Binding(
                            get: { viewModel.dateFrom ?? Date() },
                            set: { viewModel.dateFrom = $0 }
                        ),
                        displayedComponents: .date
                    )

                    DatePicker(
                        "To",
                        selection: Binding(
                            get: { viewModel.dateTo ?? Date() },
                            set: { viewModel.dateTo = $0 }
                        ),
                        displayedComponents: .date
                    )
                }

                Section {
                    Button("Apply Filter") {
                        showDateFilter = false
                        Task { await viewModel.refreshSales() }
                    }
                    .fontWeight(.semibold)

                    Button("Clear Filter", role: .destructive) {
                        viewModel.dateFrom = nil
                        viewModel.dateTo = nil
                        showDateFilter = false
                        Task { await viewModel.refreshSales() }
                    }
                }
            }
            .navigationTitle("Filter Sales")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") {
                        showDateFilter = false
                    }
                }
            }
        }
        .presentationDetents([.medium])
    }
}

// MARK: - Sale Row

struct SaleRow: View {
    let sale: Sale

    var body: some View {
        HStack(spacing: 12) {
            // Sale icon
            Circle()
                .fill(paymentColor.opacity(0.15))
                .frame(width: 40, height: 40)
                .overlay {
                    Image(systemName: "doc.text.fill")
                        .font(.subheadline)
                        .foregroundStyle(paymentColor)
                }

            VStack(alignment: .leading, spacing: 4) {
                Text(sale.saleNumber)
                    .font(.subheadline)
                    .fontWeight(.medium)

                HStack(spacing: 6) {
                    Text(sale.saleDate)
                        .font(.caption)
                        .foregroundStyle(.secondary)

                    if let customer = sale.customerName {
                        Text(customer)
                            .font(.caption)
                            .foregroundStyle(.secondary)
                            .lineLimit(1)
                    }
                }
            }

            Spacer()

            VStack(alignment: .trailing, spacing: 4) {
                Text("\(AppConfig.currencySymbol)\(sale.totalAmountDecimal)")
                    .font(.subheadline)
                    .fontWeight(.semibold)

                PaymentBadge(method: sale.paymentMethod)
            }
        }
        .padding(.vertical, 4)
    }

    private var paymentColor: Color {
        switch sale.paymentMethod.lowercased() {
        case "cash": return .green
        case "upi": return .blue
        case "card": return .purple
        case "credit": return .orange
        default: return .gray
        }
    }
}
