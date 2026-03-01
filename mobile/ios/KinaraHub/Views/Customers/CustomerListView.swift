import SwiftUI

struct CustomerListView: View {
    @StateObject private var viewModel = CustomerViewModel()
    @State private var selectedCustomer: Customer?
    @State private var showPaymentSheet = false

    var body: some View {
        Group {
            if viewModel.isLoading && viewModel.customers.isEmpty {
                ProgressView("Loading customers...")
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if let error = viewModel.errorMessage, viewModel.customers.isEmpty {
                ErrorStateView(message: error) {
                    Task { await viewModel.refresh() }
                }
                .padding(.top, 80)
            } else if viewModel.filteredCustomers.isEmpty {
                EmptyStateView(
                    icon: "person.2",
                    title: "No Customers",
                    message: viewModel.searchText.isEmpty
                        ? "No customers found."
                        : "No customers match your search."
                )
                .padding(.top, 80)
            } else {
                customerList
            }
        }
        .navigationTitle("Customers")
        .searchable(text: $viewModel.searchText, prompt: "Search by name or mobile")
        .toolbar {
            ToolbarItem(placement: .navigationBarTrailing) {
                Button {
                    viewModel.showCreateForm = true
                } label: {
                    Image(systemName: "plus")
                }
            }
        }
        .sheet(isPresented: $viewModel.showCreateForm) {
            createCustomerSheet
        }
        .sheet(isPresented: $showPaymentSheet) {
            if let customer = selectedCustomer {
                recordPaymentSheet(customer: customer)
            }
        }
        .refreshable {
            await viewModel.refresh()
        }
        .task {
            if viewModel.customers.isEmpty {
                await viewModel.loadCustomers()
            }
        }
    }

    // MARK: - Customer List

    private var customerList: some View {
        List {
            ForEach(viewModel.filteredCustomers) { customer in
                CustomerRow(customer: customer) {
                    selectedCustomer = customer
                    showPaymentSheet = true
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

    // MARK: - Create Customer Sheet

    private var createCustomerSheet: some View {
        NavigationStack {
            Form {
                Section("Customer Details") {
                    TextField("Name *", text: $viewModel.newCustomerName)
                    TextField("Mobile", text: $viewModel.newCustomerMobile)
                        .keyboardType(.phonePad)
                    TextField("Email", text: $viewModel.newCustomerEmail)
                        .keyboardType(.emailAddress)
                        .autocapitalization(.none)
                }

                if let error = viewModel.errorMessage {
                    Section {
                        Text(error)
                            .foregroundStyle(.red)
                            .font(.caption)
                    }
                }

                Section {
                    Button {
                        Task {
                            _ = await viewModel.createCustomer()
                        }
                    } label: {
                        HStack {
                            Spacer()
                            if viewModel.isCreating {
                                ProgressView()
                            } else {
                                Text("Create Customer")
                                    .fontWeight(.semibold)
                            }
                            Spacer()
                        }
                    }
                    .disabled(viewModel.isCreating)
                }
            }
            .navigationTitle("New Customer")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") {
                        viewModel.showCreateForm = false
                    }
                }
            }
        }
        .presentationDetents([.medium])
    }

    // MARK: - Record Payment Sheet

    private func recordPaymentSheet(customer: Customer) -> some View {
        NavigationStack {
            Form {
                Section("Customer") {
                    HStack {
                        Text(customer.name)
                            .font(.headline)
                        Spacer()
                        VStack(alignment: .trailing) {
                            Text("Outstanding")
                                .font(.caption)
                                .foregroundStyle(.secondary)
                            Text("\(AppConfig.currencySymbol)\(customer.outstandingBalanceDecimal)")
                                .font(.headline)
                                .foregroundStyle(.red)
                        }
                    }
                }

                Section("Payment Details") {
                    TextField("Amount", text: $viewModel.paymentAmount)
                        .keyboardType(.decimalPad)

                    Picker("Payment Method", selection: $viewModel.paymentMethod) {
                        ForEach([PaymentMethod.cash, .upi, .card]) { method in
                            Text(method.displayName).tag(method)
                        }
                    }

                    TextField("Notes (optional)", text: $viewModel.paymentNotes)
                }

                if let error = viewModel.errorMessage {
                    Section {
                        Text(error)
                            .foregroundStyle(.red)
                            .font(.caption)
                    }
                }

                Section {
                    Button {
                        Task {
                            let success = await viewModel.recordPayment(customerId: customer.id)
                            if success {
                                showPaymentSheet = false
                            }
                        }
                    } label: {
                        HStack {
                            Spacer()
                            if viewModel.isRecordingPayment {
                                ProgressView()
                            } else {
                                Text("Record Payment")
                                    .fontWeight(.semibold)
                            }
                            Spacer()
                        }
                    }
                    .disabled(viewModel.isRecordingPayment)
                }
            }
            .navigationTitle("Record Payment")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") {
                        showPaymentSheet = false
                    }
                }
            }
        }
        .presentationDetents([.medium, .large])
    }
}

// MARK: - Customer Row

struct CustomerRow: View {
    let customer: Customer
    let onRecordPayment: () -> Void

    var body: some View {
        HStack(spacing: 12) {
            // Avatar
            Circle()
                .fill(customer.hasOutstandingBalance ? Color.red.opacity(0.15) : Color(.systemGray5))
                .frame(width: 44, height: 44)
                .overlay {
                    Text(String(customer.name.prefix(1)).uppercased())
                        .font(.headline)
                        .foregroundStyle(customer.hasOutstandingBalance ? .red : .secondary)
                }

            VStack(alignment: .leading, spacing: 4) {
                Text(customer.name)
                    .font(.subheadline)
                    .fontWeight(.medium)

                if let mobile = customer.mobile {
                    Text(mobile)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }

            Spacer()

            VStack(alignment: .trailing, spacing: 4) {
                if customer.hasOutstandingBalance {
                    Text("\(AppConfig.currencySymbol)\(customer.outstandingBalanceDecimal)")
                        .font(.subheadline)
                        .fontWeight(.semibold)
                        .foregroundStyle(.red)

                    Button("Pay") {
                        onRecordPayment()
                    }
                    .font(.caption)
                    .fontWeight(.medium)
                    .padding(.horizontal, 10)
                    .padding(.vertical, 4)
                    .background(Color.indigo)
                    .foregroundStyle(.white)
                    .cornerRadius(6)
                } else {
                    Text("No dues")
                        .font(.caption)
                        .foregroundStyle(.green)
                }
            }
        }
        .padding(.vertical, 4)
    }
}
