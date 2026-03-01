import Foundation
import KeychainAccess

// MARK: - TokenManager Protocol

@MainActor
protocol TokenManagerProtocol: AnyObject {
    var isAuthenticated: Bool { get }
    var accessToken: String? { get }
    var refreshToken: String? { get }
    func saveTokens(accessToken: String, refreshToken: String)
    func clearTokens()
}

@MainActor
final class TokenManager: ObservableObject, TokenManagerProtocol {
    static let shared = TokenManager()

    private let keychain = Keychain(service: "com.kinarahub.ios")
    private let accessTokenKey = "access_token"
    private let refreshTokenKey = "refresh_token"

    @Published private(set) var isAuthenticated: Bool = false

    private init() {
        isAuthenticated = accessToken != nil
    }

    var accessToken: String? {
        try? keychain.get(accessTokenKey)
    }

    var refreshToken: String? {
        try? keychain.get(refreshTokenKey)
    }

    func saveTokens(accessToken: String, refreshToken: String) {
        try? keychain.set(accessToken, key: accessTokenKey)
        try? keychain.set(refreshToken, key: refreshTokenKey)
        isAuthenticated = true
    }

    func clearTokens() {
        try? keychain.remove(accessTokenKey)
        try? keychain.remove(refreshTokenKey)
        isAuthenticated = false
    }

    func updateAccessToken(_ token: String) {
        try? keychain.set(token, key: accessTokenKey)
    }
}
