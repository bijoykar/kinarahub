import Foundation

enum APIError: LocalizedError {
    case invalidURL
    case noData
    case decodingError(Error)
    case serverError(String)
    case unauthorized
    case conflict
    case validationError(String)
    case networkError(Error)

    var errorDescription: String? {
        switch self {
        case .invalidURL:
            return "Invalid URL"
        case .noData:
            return "No data received"
        case .decodingError(let error):
            return "Failed to decode response: \(error.localizedDescription)"
        case .serverError(let message):
            return message
        case .unauthorized:
            return "Session expired. Please log in again."
        case .conflict:
            return "Data was modified by another user. Please refresh and try again."
        case .validationError(let message):
            return message
        case .networkError(let error):
            return "Network error: \(error.localizedDescription)"
        }
    }
}

enum HTTPMethod: String {
    case get = "GET"
    case post = "POST"
    case put = "PUT"
    case delete = "DELETE"
}

// MARK: - APIClient Protocol

@MainActor
protocol APIClientProtocol {
    func get<T: Codable>(url: String, queryParams: [String: String]?, authenticated: Bool) async throws -> APIResponse<T>
    func post<T: Codable>(url: String, body: (any Encodable)?, authenticated: Bool) async throws -> APIResponse<T>
    func put<T: Codable>(url: String, body: (any Encodable)?, authenticated: Bool) async throws -> APIResponse<T>
    func delete<T: Codable>(url: String, authenticated: Bool) async throws -> APIResponse<T>
}

@MainActor
final class APIClient: APIClientProtocol {
    static let shared = APIClient()

    private let session: URLSession
    private let decoder: JSONDecoder
    private let encoder: JSONEncoder
    private var isRefreshing = false

    private init() {
        let config = URLSessionConfiguration.default
        config.timeoutIntervalForRequest = 30
        config.timeoutIntervalForResource = 60
        session = URLSession(configuration: config)

        decoder = JSONDecoder()
        encoder = JSONEncoder()
    }

    // MARK: - Generic Request

    func request<T: Codable>(
        url urlString: String,
        method: HTTPMethod = .get,
        body: (any Encodable)? = nil,
        queryParams: [String: String]? = nil,
        authenticated: Bool = true
    ) async throws -> APIResponse<T> {
        guard var urlComponents = URLComponents(string: urlString) else {
            throw APIError.invalidURL
        }

        if let queryParams {
            urlComponents.queryItems = queryParams.map {
                URLQueryItem(name: $0.key, value: $0.value)
            }
        }

        guard let url = urlComponents.url else {
            throw APIError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = method.rawValue
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("application/json", forHTTPHeaderField: "Accept")

        if authenticated, let token = TokenManager.shared.accessToken {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }

        if let body {
            request.httpBody = try encoder.encode(body)
        }

        let data: Data
        let response: URLResponse

        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APIError.networkError(error)
        }

        guard let httpResponse = response as? HTTPURLResponse else {
            throw APIError.noData
        }

        // Handle 401 — attempt token refresh
        if httpResponse.statusCode == 401 && authenticated {
            let refreshed = await refreshTokenIfNeeded()
            if refreshed {
                return try await self.request(
                    url: urlString,
                    method: method,
                    body: body,
                    queryParams: queryParams,
                    authenticated: true
                )
            } else {
                TokenManager.shared.clearTokens()
                NotificationCenter.default.post(name: .authSessionExpired, object: nil)
                throw APIError.unauthorized
            }
        }

        if httpResponse.statusCode == 409 {
            throw APIError.conflict
        }

        do {
            let apiResponse = try decoder.decode(APIResponse<T>.self, from: data)

            if !apiResponse.success, let error = apiResponse.error {
                if httpResponse.statusCode == 422 {
                    throw APIError.validationError(error)
                }
                throw APIError.serverError(error)
            }

            return apiResponse
        } catch let error as APIError {
            throw error
        } catch {
            throw APIError.decodingError(error)
        }
    }

    // MARK: - Convenience Methods

    func get<T: Codable>(
        url: String,
        queryParams: [String: String]? = nil,
        authenticated: Bool = true
    ) async throws -> APIResponse<T> {
        try await request(url: url, method: .get, queryParams: queryParams, authenticated: authenticated)
    }

    func post<T: Codable>(
        url: String,
        body: (any Encodable)? = nil,
        authenticated: Bool = true
    ) async throws -> APIResponse<T> {
        try await request(url: url, method: .post, body: body, authenticated: authenticated)
    }

    func put<T: Codable>(
        url: String,
        body: (any Encodable)? = nil,
        authenticated: Bool = true
    ) async throws -> APIResponse<T> {
        try await request(url: url, method: .put, body: body, authenticated: authenticated)
    }

    func delete<T: Codable>(
        url: String,
        authenticated: Bool = true
    ) async throws -> APIResponse<T> {
        try await request(url: url, method: .delete, authenticated: authenticated)
    }

    // MARK: - Token Refresh

    private func refreshTokenIfNeeded() async -> Bool {
        guard !isRefreshing else { return false }
        isRefreshing = true
        defer { isRefreshing = false }

        guard let refreshToken = TokenManager.shared.refreshToken else {
            return false
        }

        let body = RefreshRequest(refreshToken: refreshToken)

        do {
            let response: APIResponse<RefreshResponse> = try await request(
                url: APIEndpoints.Auth.refresh,
                method: .post,
                body: body,
                authenticated: false
            )

            if let data = response.data {
                TokenManager.shared.saveTokens(
                    accessToken: data.accessToken,
                    refreshToken: data.refreshToken
                )
                return true
            }
            return false
        } catch {
            return false
        }
    }
}

// MARK: - Notification

extension Notification.Name {
    static let authSessionExpired = Notification.Name("authSessionExpired")
}
