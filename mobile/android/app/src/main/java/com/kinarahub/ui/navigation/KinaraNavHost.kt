package com.kinarahub.ui.navigation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import com.kinarahub.ui.screens.customers.CustomerListScreen
import com.kinarahub.ui.screens.dashboard.DashboardScreen
import com.kinarahub.ui.screens.inventory.InventoryListScreen
import com.kinarahub.ui.screens.inventory.ProductDetailScreen
import com.kinarahub.ui.screens.login.LoginScreen
import com.kinarahub.ui.screens.login.LoginViewModel
import com.kinarahub.ui.screens.sales.POSSaleScreen
import com.kinarahub.ui.screens.sales.SalesHistoryScreen

@Composable
fun KinaraNavHost(
    navController: NavHostController,
    startDestination: String
) {
    NavHost(
        navController = navController,
        startDestination = startDestination
    ) {
        composable(Screen.Login.route) {
            val viewModel: LoginViewModel = hiltViewModel()
            val loginSuccess by viewModel.loginSuccess.collectAsState()

            LaunchedEffect(loginSuccess) {
                if (loginSuccess) {
                    navController.navigate(Screen.Dashboard.route) {
                        popUpTo(Screen.Login.route) { inclusive = true }
                    }
                }
            }

            LoginScreen(viewModel = viewModel)
        }

        composable(Screen.Dashboard.route) {
            DashboardScreen(
                onNavigateToInventory = {
                    navController.navigate(Screen.Inventory.route)
                },
                onNavigateToSales = {
                    navController.navigate(Screen.SalesHistory.route)
                },
                onNavigateToPOS = {
                    navController.navigate(Screen.POSSale.route)
                },
                onNavigateToCustomers = {
                    navController.navigate(Screen.Customers.route)
                },
                onLogout = {
                    navController.navigate(Screen.Login.route) {
                        popUpTo(0) { inclusive = true }
                    }
                }
            )
        }

        composable(Screen.Inventory.route) {
            InventoryListScreen(
                onProductClick = { productId ->
                    navController.navigate(Screen.ProductDetail.createRoute(productId))
                },
                onBack = { navController.popBackStack() }
            )
        }

        composable(
            route = Screen.ProductDetail.route,
            arguments = listOf(navArgument("productId") { type = NavType.IntType })
        ) { backStackEntry ->
            val productId = backStackEntry.arguments?.getInt("productId") ?: return@composable
            ProductDetailScreen(
                productId = productId,
                onBack = { navController.popBackStack() }
            )
        }

        composable(Screen.POSSale.route) {
            POSSaleScreen(
                onSaleComplete = { saleId ->
                    navController.navigate(Screen.SalesHistory.route) {
                        popUpTo(Screen.Dashboard.route)
                    }
                },
                onBack = { navController.popBackStack() }
            )
        }

        composable(Screen.SalesHistory.route) {
            SalesHistoryScreen(
                onBack = { navController.popBackStack() }
            )
        }

        composable(Screen.Customers.route) {
            CustomerListScreen(
                onBack = { navController.popBackStack() }
            )
        }
    }
}
