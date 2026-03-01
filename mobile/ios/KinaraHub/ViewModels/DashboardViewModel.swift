import Foundation

@MainActor
final class DashboardViewModel: ObservableObject {
    @Published var summary: DashboardSummary?
    @Published var isLoading = false
    @Published var errorMessage: String?

    // Sales trend chart
    @Published var trendDataPoints: [ChartDataPoint] = []
    @Published var selectedPeriod: TrendPeriod = .week
    @Published var isTrendLoading = false

    private let apiClient: APIClientProtocol

    init(apiClient: APIClientProtocol? = nil) {
        self.apiClient = apiClient ?? APIClient.shared
    }

    func loadSummary() async {
        isLoading = true
        errorMessage = nil

        do {
            let response: APIResponse<DashboardSummary> = try await apiClient.get(
                url: APIEndpoints.Dashboard.summary
            )
            summary = response.data
        } catch let error as APIError {
            errorMessage = error.errorDescription
        } catch {
            errorMessage = error.localizedDescription
        }

        isLoading = false
    }

    func loadSalesTrend(period: TrendPeriod) async {
        isTrendLoading = true
        selectedPeriod = period

        do {
            let response: APIResponse<SalesTrendResponse> = try await apiClient.get(
                url: APIEndpoints.Dashboard.chart,
                queryParams: ["type": "sales_trend", "period": period.rawValue]
            )
            if let data = response.data {
                trendDataPoints = zip(data.labels, data.amounts).map { label, amount in
                    ChartDataPoint(label: label, amount: amount)
                }
            }
        } catch {
            trendDataPoints = []
        }

        isTrendLoading = false
    }
}
