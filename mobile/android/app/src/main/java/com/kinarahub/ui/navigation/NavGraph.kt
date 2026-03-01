package com.kinarahub.ui.navigation

sealed class Screen(val route: String) {
    data object Login : Screen("login")
    data object Dashboard : Screen("dashboard")
    data object Inventory : Screen("inventory")
    data object ProductDetail : Screen("product/{productId}") {
        fun createRoute(productId: Int) = "product/$productId"
    }
    data object POSSale : Screen("pos")
    data object SalesHistory : Screen("sales")
    data object SaleDetail : Screen("sale/{saleId}") {
        fun createRoute(saleId: Int) = "sale/$saleId"
    }
    data object Customers : Screen("customers")
}
