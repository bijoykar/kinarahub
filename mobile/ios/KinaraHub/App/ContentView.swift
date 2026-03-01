import SwiftUI

struct ContentView: View {
    @EnvironmentObject var authViewModel: AuthViewModel
    @EnvironmentObject var router: AppRouter

    var body: some View {
        Group {
            if authViewModel.isAuthenticated {
                MainTabView()
                    .environmentObject(router)
            } else {
                LoginView()
            }
        }
        .animation(.easeInOut, value: authViewModel.isAuthenticated)
    }
}

struct MainTabView: View {
    @EnvironmentObject var router: AppRouter

    var body: some View {
        TabView(selection: $router.selectedTab) {
            DashboardTab()
                .tabItem {
                    Label(AppRouter.Tab.dashboard.rawValue, systemImage: AppRouter.Tab.dashboard.icon)
                }
                .tag(AppRouter.Tab.dashboard)

            InventoryTab()
                .tabItem {
                    Label(AppRouter.Tab.inventory.rawValue, systemImage: AppRouter.Tab.inventory.icon)
                }
                .tag(AppRouter.Tab.inventory)

            POSSaleView()
                .tabItem {
                    Label(AppRouter.Tab.pos.rawValue, systemImage: AppRouter.Tab.pos.icon)
                }
                .tag(AppRouter.Tab.pos)

            SalesHistoryTab()
                .tabItem {
                    Label(AppRouter.Tab.sales.rawValue, systemImage: AppRouter.Tab.sales.icon)
                }
                .tag(AppRouter.Tab.sales)

            CustomerTab()
                .tabItem {
                    Label(AppRouter.Tab.customers.rawValue, systemImage: AppRouter.Tab.customers.icon)
                }
                .tag(AppRouter.Tab.customers)
        }
        .tint(.indigo)
    }
}

// MARK: - Tab Wrappers with NavigationStack

struct DashboardTab: View {
    @EnvironmentObject var router: AppRouter

    var body: some View {
        NavigationStack(path: $router.path) {
            DashboardView()
                .navigationDestination(for: Route.self) { route in
                    routeDestination(route)
                }
        }
    }
}

struct InventoryTab: View {
    @EnvironmentObject var router: AppRouter

    var body: some View {
        NavigationStack {
            InventoryListView()
                .navigationDestination(for: Route.self) { route in
                    routeDestination(route)
                }
        }
    }
}

struct SalesHistoryTab: View {
    var body: some View {
        NavigationStack {
            SalesHistoryView()
        }
    }
}

struct CustomerTab: View {
    var body: some View {
        NavigationStack {
            CustomerListView()
        }
    }
}

// MARK: - Route Destination Builder

@ViewBuilder
func routeDestination(_ route: Route) -> some View {
    switch route {
    case .login:
        LoginView()
    case .dashboard:
        DashboardView()
    case .inventoryList:
        InventoryListView()
    case .productDetail(let id):
        ProductDetailView(productId: id)
    case .posSale:
        POSSaleView()
    case .salesHistory:
        SalesHistoryView()
    case .customerList:
        CustomerListView()
    }
}
