import SwiftUI

struct POSSaleView: View {
    @StateObject private var viewModel = SalesViewModel()
    @StateObject private var customerViewModel = CustomerViewModel()
    @State private var showCustomerPicker = false
    @State private var showConfirmation = false

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                // Product search
                productSearchSection

                // Cart
                if viewModel.isCartEmpty {
                    Spacer()
                    EmptyStateView(
                        icon: "cart",
                        title: "Cart is Empty",
                        message: "Search for products above to add them to the cart."
                    )
                    Spacer()
                } else {
                    cartSection
                }

                // Bottom bar
                checkoutBar
            }
            .navigationTitle("New Sale")
            .navigationBarTitleDisplayMode(.inline)
            .alert("Sale Created", isPresented: $viewModel.showSaleSuccess) {
                Button("OK", role: .cancel) {}
            } message: {
                if let sale = viewModel.lastCreatedSale {
                    Text("Sale \(sale.saleNumber) created successfully.")
                }
            }
            .sheet(isPresented: $showCustomerPicker) {
                customerPickerSheet
            }
            .alert("Confirm Sale", isPresented: $showConfirmation) {
                Button("Cancel", role: .cancel) {}
                Button("Confirm") {
                    Task { await viewModel.submitSale() }
                }
            } message: {
                Text("Create sale for \(AppConfig.currencySymbol)\(viewModel.cartTotal)?")
            }
        }
    }

    // MARK: - Product Search

    private var productSearchSection: some View {
        VStack(spacing: 0) {
            HStack {
                Image(systemName: "magnifyingglass")
                    .foregroundStyle(.secondary)
                TextField("Search products by name or SKU", text: $viewModel.posSearchText)
                    .autocapitalization(.none)
                    .disableAutocorrection(true)
                    .onChange(of: viewModel.posSearchText) { query in
                        Task { await viewModel.searchProducts(query: query) }
                    }
                if !viewModel.posSearchText.isEmpty {
                    Button {
                        viewModel.posSearchText = ""
                        viewModel.searchResults = []
                    } label: {
                        Image(systemName: "xmark.circle.fill")
                            .foregroundStyle(.secondary)
                    }
                }
            }
            .padding()
            .background(Color(.systemGray6))

            // Search results dropdown
            if !viewModel.searchResults.isEmpty {
                ScrollView {
                    VStack(spacing: 0) {
                        ForEach(viewModel.searchResults) { product in
                            Button {
                                viewModel.addToCart(product: product)
                                viewModel.posSearchText = ""
                                viewModel.searchResults = []
                            } label: {
                                HStack {
                                    VStack(alignment: .leading, spacing: 2) {
                                        Text(product.name)
                                            .font(.subheadline)
                                            .foregroundStyle(.primary)
                                        HStack {
                                            Text(product.sku)
                                                .font(.caption)
                                                .foregroundStyle(.secondary)
                                            Text("Stock: \(product.stockQuantityDecimal)")
                                                .font(.caption)
                                                .foregroundStyle(.secondary)
                                        }
                                    }
                                    Spacer()
                                    Text("\(AppConfig.currencySymbol)\(product.sellingPriceDecimal)")
                                        .font(.subheadline)
                                        .fontWeight(.medium)
                                }
                                .padding(.horizontal)
                                .padding(.vertical, 10)
                            }
                            Divider()
                        }
                    }
                }
                .frame(maxHeight: 200)
                .background(Color(.systemBackground))
                .shadow(radius: 4)
            }
        }
    }

    // MARK: - Cart Section

    private var cartSection: some View {
        List {
            ForEach(Array(viewModel.cartItems.enumerated()), id: \.element.id) { index, item in
                CartItemRow(
                    item: item,
                    onQuantityChange: { newQty in
                        viewModel.updateCartItemQuantity(at: index, quantity: newQty)
                    },
                    onRemove: {
                        viewModel.removeFromCart(at: index)
                    }
                )
            }
        }
        .listStyle(.plain)
    }

    // MARK: - Checkout Bar

    private var checkoutBar: some View {
        VStack(spacing: 12) {
            Divider()

            if let error = viewModel.validationError {
                Text(error)
                    .font(.caption)
                    .foregroundStyle(.red)
                    .padding(.horizontal)
            }

            // Payment method picker
            HStack(spacing: 8) {
                ForEach(PaymentMethod.allCases) { method in
                    Button {
                        viewModel.selectedPaymentMethod = method
                    } label: {
                        Text(method.displayName)
                            .font(.caption)
                            .fontWeight(.medium)
                            .padding(.horizontal, 12)
                            .padding(.vertical, 8)
                            .background(
                                viewModel.selectedPaymentMethod == method
                                    ? Color.indigo : Color(.systemGray5)
                            )
                            .foregroundStyle(
                                viewModel.selectedPaymentMethod == method
                                    ? .white : .primary
                            )
                            .cornerRadius(8)
                    }
                }
            }
            .padding(.horizontal)

            // Customer selection (shown for credit)
            if viewModel.selectedPaymentMethod == .credit {
                Button {
                    showCustomerPicker = true
                    Task { await customerViewModel.loadCustomers() }
                } label: {
                    HStack {
                        Image(systemName: "person.fill")
                        Text(viewModel.selectedCustomer?.name ?? "Select Customer")
                            .font(.subheadline)
                        Spacer()
                        Image(systemName: "chevron.right")
                    }
                    .padding()
                    .background(Color(.systemGray6))
                    .cornerRadius(10)
                }
                .padding(.horizontal)
            }

            // Total and submit
            HStack {
                VStack(alignment: .leading) {
                    Text("Total")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                    Text("\(AppConfig.currencySymbol)\(viewModel.cartTotal)")
                        .font(.title2)
                        .fontWeight(.bold)
                }

                Spacer()

                Button {
                    showConfirmation = true
                } label: {
                    Group {
                        if viewModel.isSubmitting {
                            ProgressView()
                                .tint(.white)
                        } else {
                            Label("Submit Sale", systemImage: "checkmark.circle.fill")
                        }
                    }
                    .fontWeight(.semibold)
                    .padding(.horizontal, 24)
                    .padding(.vertical, 12)
                    .background(viewModel.isCartEmpty ? Color.gray : Color.indigo)
                    .foregroundStyle(.white)
                    .cornerRadius(12)
                }
                .disabled(viewModel.isCartEmpty || viewModel.isSubmitting)
            }
            .padding(.horizontal)
            .padding(.bottom, 8)
        }
        .background(Color(.systemBackground))
    }

    // MARK: - Customer Picker Sheet

    private var customerPickerSheet: some View {
        NavigationStack {
            List {
                ForEach(customerViewModel.filteredCustomers) { customer in
                    Button {
                        viewModel.selectedCustomer = customer
                        showCustomerPicker = false
                    } label: {
                        HStack {
                            VStack(alignment: .leading) {
                                Text(customer.name)
                                    .font(.subheadline)
                                    .foregroundStyle(.primary)
                                if let mobile = customer.mobile {
                                    Text(mobile)
                                        .font(.caption)
                                        .foregroundStyle(.secondary)
                                }
                            }
                            Spacer()
                            if viewModel.selectedCustomer?.id == customer.id {
                                Image(systemName: "checkmark.circle.fill")
                                    .foregroundStyle(.indigo)
                            }
                        }
                    }
                }
            }
            .listStyle(.plain)
            .searchable(text: $customerViewModel.searchText, prompt: "Search customers")
            .navigationTitle("Select Customer")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") {
                        showCustomerPicker = false
                    }
                }
            }
        }
    }
}

// MARK: - Cart Item Row

struct CartItemRow: View {
    let item: CartItem
    let onQuantityChange: (Decimal) -> Void
    let onRemove: () -> Void

    var body: some View {
        HStack(spacing: 12) {
            VStack(alignment: .leading, spacing: 2) {
                Text(item.name)
                    .font(.subheadline)
                    .fontWeight(.medium)
                    .lineLimit(1)
                Text(item.sku)
                    .font(.caption)
                    .foregroundStyle(.secondary)
                Text("\(AppConfig.currencySymbol)\(item.unitPrice) each")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }

            Spacer()

            // Quantity stepper
            HStack(spacing: 8) {
                Button {
                    let newQty = item.quantity - 1
                    onQuantityChange(newQty)
                } label: {
                    Image(systemName: "minus.circle.fill")
                        .font(.title3)
                        .foregroundStyle(.secondary)
                }

                Text("\(item.quantity)")
                    .font(.subheadline)
                    .fontWeight(.medium)
                    .frame(minWidth: 30)

                Button {
                    let newQty = item.quantity + 1
                    onQuantityChange(newQty)
                } label: {
                    Image(systemName: "plus.circle.fill")
                        .font(.title3)
                        .foregroundStyle(.indigo)
                }
            }

            VStack(alignment: .trailing) {
                Text("\(AppConfig.currencySymbol)\(item.lineTotal)")
                    .font(.subheadline)
                    .fontWeight(.semibold)
            }
            .frame(minWidth: 60, alignment: .trailing)
        }
        .swipeActions(edge: .trailing) {
            Button(role: .destructive) {
                onRemove()
            } label: {
                Label("Remove", systemImage: "trash")
            }
        }
    }
}
