import SwiftUI

enum Route: Hashable {
    case login
    case dashboard
    case inventoryList
    case productDetail(id: Int)
    case posSale
    case salesHistory
    case customerList
}

@MainActor
final class AppRouter: ObservableObject {
    @Published var path = NavigationPath()
    @Published var selectedTab: Tab = .dashboard

    enum Tab: String, CaseIterable, Identifiable {
        case dashboard = "Dashboard"
        case inventory = "Inventory"
        case pos = "POS"
        case sales = "Sales"
        case customers = "Customers"

        var id: String { rawValue }

        var icon: String {
            switch self {
            case .dashboard: return "chart.bar.fill"
            case .inventory: return "cube.box.fill"
            case .pos: return "cart.fill"
            case .sales: return "list.bullet.rectangle.fill"
            case .customers: return "person.2.fill"
            }
        }
    }

    func navigate(to route: Route) {
        path.append(route)
    }

    func popToRoot() {
        path = NavigationPath()
    }

    func goBack() {
        if !path.isEmpty {
            path.removeLast()
        }
    }
}
