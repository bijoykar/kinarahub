import Foundation

enum AppConfig {
    #if DEBUG
    static let apiBaseURL = "http://localhost/kinarahub/api/v1"
    #else
    static let apiBaseURL = "https://api.kinarahub.com/api/v1"
    #endif

    static let currency = "INR"
    static let currencySymbol = "\u{20B9}"
    static let defaultPageSize = 20
    static let accessTokenExpiryMinutes = 15
    static let refreshTokenExpiryDays = 30
}
