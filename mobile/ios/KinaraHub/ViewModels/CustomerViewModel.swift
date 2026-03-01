import Foundation

@MainActor
final class CustomerViewModel: ObservableObject {
    @Published var customers: [Customer] = []
    @Published var isLoading = false
    @Published var errorMessage: String?
    @Published var searchText = ""
    @Published var currentPage = 1
    @Published var totalPages = 1

    // Create customer
    @Published var newCustomerName = ""
    @Published var newCustomerMobile = ""
    @Published var newCustomerEmail = ""
    @Published var isCreating = false
    @Published var showCreateForm = false

    // Credit history
    @Published var selectedCustomerCredits: [CustomerCredit] = []
    @Published var isLoadingCredits = false

    // Record payment
    @Published var paymentAmount = ""
    @Published var paymentMethod: PaymentMethod = .cash
    @Published var paymentNotes = ""
    @Published var isRecordingPayment = false

    private let apiClient: APIClientProtocol

    init(apiClient: APIClientProtocol? = nil) {
        self.apiClient = apiClient ?? APIClient.shared
    }

    var filteredCustomers: [Customer] {
        if searchText.isEmpty {
            return customers
        }
        let query = searchText.lowercased()
        return customers.filter {
            $0.name.lowercased().contains(query) ||
            ($0.mobile?.lowercased().contains(query) ?? false)
        }
    }

    func loadCustomers(page: Int = 1) async {
        isLoading = true
        errorMessage = nil

        let params: [String: String] = [
            "page": "\(page)",
            "per_page": "\(AppConfig.defaultPageSize)"
        ]

        do {
            let response: APIResponse<[Customer]> = try await apiClient.get(
                url: APIEndpoints.Customers.list,
                queryParams: params
            )
            if page == 1 {
                customers = response.data ?? []
            } else {
                customers.append(contentsOf: response.data ?? [])
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
        await loadCustomers(page: currentPage + 1)
    }

    func refresh() async {
        currentPage = 1
        await loadCustomers(page: 1)
    }

    func createCustomer() async -> Customer? {
        guard !newCustomerName.isEmpty else {
            errorMessage = "Customer name is required."
            return nil
        }

        isCreating = true
        errorMessage = nil

        let body = CreateCustomerRequest(
            name: newCustomerName,
            mobile: newCustomerMobile.isEmpty ? nil : newCustomerMobile,
            email: newCustomerEmail.isEmpty ? nil : newCustomerEmail
        )

        do {
            let response: APIResponse<Customer> = try await apiClient.post(
                url: APIEndpoints.Customers.create,
                body: body
            )
            if let customer = response.data {
                customers.insert(customer, at: 0)
                newCustomerName = ""
                newCustomerMobile = ""
                newCustomerEmail = ""
                showCreateForm = false
                return customer
            }
        } catch let error as APIError {
            errorMessage = error.errorDescription
        } catch {
            errorMessage = error.localizedDescription
        }

        isCreating = false
        return nil
    }

    func loadCredits(customerId: Int) async {
        isLoadingCredits = true

        do {
            let response: APIResponse<[CustomerCredit]> = try await apiClient.get(
                url: APIEndpoints.Customers.credits(customerId)
            )
            selectedCustomerCredits = response.data ?? []
        } catch {
            errorMessage = error.localizedDescription
        }

        isLoadingCredits = false
    }

    func recordPayment(customerId: Int) async -> Bool {
        guard !paymentAmount.isEmpty else {
            errorMessage = "Payment amount is required."
            return false
        }

        isRecordingPayment = true
        errorMessage = nil

        let body = RecordPaymentRequest(
            amount: paymentAmount,
            paymentMethod: paymentMethod.rawValue,
            notes: paymentNotes.isEmpty ? nil : paymentNotes
        )

        do {
            let _: APIResponse<EmptyResponse> = try await apiClient.post(
                url: APIEndpoints.Customers.payments(customerId),
                body: body
            )
            paymentAmount = ""
            paymentNotes = ""
            isRecordingPayment = false
            await refresh()
            return true
        } catch let error as APIError {
            errorMessage = error.errorDescription
        } catch {
            errorMessage = error.localizedDescription
        }

        isRecordingPayment = false
        return false
    }
}
