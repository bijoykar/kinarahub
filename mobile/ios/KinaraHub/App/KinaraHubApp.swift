import SwiftUI

@main
struct KinaraHubApp: App {
    @StateObject private var tokenManager = TokenManager.shared
    @StateObject private var authViewModel = AuthViewModel()
    @StateObject private var router = AppRouter()

    var body: some Scene {
        WindowGroup {
            ContentView()
                .environmentObject(tokenManager)
                .environmentObject(authViewModel)
                .environmentObject(router)
        }
    }
}
