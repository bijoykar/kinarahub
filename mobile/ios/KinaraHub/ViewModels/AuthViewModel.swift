import Foundation

@MainActor
final class AuthViewModel: ObservableObject {
    @Published var email = ""
    @Published var password = ""
    @Published var isLoading = false
    @Published var errorMessage: String?
    @Published var isAuthenticated = false

    private let apiClient: APIClientProtocol
    private let tokenManager: TokenManagerProtocol

    init(apiClient: APIClientProtocol? = nil, tokenManager: TokenManagerProtocol? = nil) {
        self.apiClient = apiClient ?? APIClient.shared
        self.tokenManager = tokenManager ?? TokenManager.shared
        isAuthenticated = self.tokenManager.isAuthenticated

        NotificationCenter.default.addObserver(
            forName: .authSessionExpired,
            object: nil,
            queue: .main
        ) { [weak self] _ in
            Task { @MainActor in
                self?.isAuthenticated = false
            }
        }
    }

    func login() async {
        guard !email.isEmpty, !password.isEmpty else {
            errorMessage = "Please enter email and password."
            return
        }

        isLoading = true
        errorMessage = nil

        do {
            let body = LoginRequest(email: email, password: password)
            let response: APIResponse<LoginResponse> = try await apiClient.post(
                url: APIEndpoints.Auth.login,
                body: body,
                authenticated: false
            )

            if let data = response.data {
                tokenManager.saveTokens(
                    accessToken: data.accessToken,
                    refreshToken: data.refreshToken
                )
                isAuthenticated = true
                email = ""
                password = ""
            } else {
                errorMessage = response.error ?? "Login failed."
            }
        } catch let error as APIError {
            errorMessage = error.errorDescription
        } catch {
            errorMessage = error.localizedDescription
        }

        isLoading = false
    }

    func logout() async {
        isLoading = true

        if let refreshToken = tokenManager.refreshToken {
            let body = LogoutRequest(refreshToken: refreshToken)
            let _: APIResponse<EmptyResponse>? = try? await apiClient.post(
                url: APIEndpoints.Auth.logout,
                body: body,
                authenticated: true
            )
        }

        tokenManager.clearTokens()
        isAuthenticated = false
        isLoading = false
    }
}

struct EmptyResponse: Codable {}
