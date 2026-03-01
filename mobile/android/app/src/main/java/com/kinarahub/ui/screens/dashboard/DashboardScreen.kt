package com.kinarahub.ui.screens.dashboard

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.github.mikephil.charting.data.Entry
import com.kinarahub.ui.components.*
import com.kinarahub.ui.theme.StockLowColor
import com.kinarahub.ui.theme.StockOutColor

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(
    viewModel: DashboardViewModel = hiltViewModel(),
    onNavigateToInventory: () -> Unit,
    onNavigateToSales: () -> Unit,
    onNavigateToPOS: () -> Unit,
    onNavigateToCustomers: () -> Unit,
    onLogout: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val selectedPeriod by viewModel.selectedPeriod.collectAsState()
    val salesTrend by viewModel.salesTrend.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text(
                            text = viewModel.storeName,
                            fontWeight = FontWeight.Bold,
                            style = MaterialTheme.typography.titleMedium
                        )
                        Text(
                            text = "Welcome, ${viewModel.userName}",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onPrimary.copy(alpha = 0.8f)
                        )
                    }
                },
                actions = {
                    IconButton(onClick = {
                        viewModel.logout()
                        onLogout()
                    }) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.Logout,
                            contentDescription = "Logout"
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary,
                    titleContentColor = MaterialTheme.colorScheme.onPrimary,
                    actionIconContentColor = MaterialTheme.colorScheme.onPrimary
                )
            )
        },
        bottomBar = {
            NavigationBar {
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Dashboard, contentDescription = null) },
                    label = { Text("Dashboard") },
                    selected = true,
                    onClick = { }
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Inventory2, contentDescription = null) },
                    label = { Text("Inventory") },
                    selected = false,
                    onClick = onNavigateToInventory
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.PointOfSale, contentDescription = null) },
                    label = { Text("POS") },
                    selected = false,
                    onClick = onNavigateToPOS
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Receipt, contentDescription = null) },
                    label = { Text("Sales") },
                    selected = false,
                    onClick = onNavigateToSales
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.People, contentDescription = null) },
                    label = { Text("Customers") },
                    selected = false,
                    onClick = onNavigateToCustomers
                )
            }
        },
        floatingActionButton = {
            ExtendedFloatingActionButton(
                onClick = onNavigateToPOS,
                icon = { Icon(Icons.Default.Add, contentDescription = null) },
                text = { Text("New Sale") },
                containerColor = MaterialTheme.colorScheme.primary
            )
        }
    ) { padding ->
        when (val state = uiState) {
            is DashboardUiState.Loading -> LoadingState(modifier = Modifier.padding(padding))
            is DashboardUiState.Error -> ErrorState(
                message = state.message,
                onRetry = viewModel::loadDashboard,
                modifier = Modifier.padding(padding)
            )
            is DashboardUiState.Success -> {
                val summary = state.summary
                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(padding),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    // KPI Cards Row 1
                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            KpiCard(
                                title = "Sales Today",
                                value = formatCurrency(summary.salesToday),
                                subtitle = summary.salesTodayChange?.let {
                                    val sign = if (it >= 0) "+" else ""
                                    "$sign${"%.1f".format(it)}% vs yesterday"
                                },
                                modifier = Modifier.weight(1f)
                            )
                            KpiCard(
                                title = "This Week",
                                value = formatCurrency(summary.salesThisWeek),
                                modifier = Modifier.weight(1f)
                            )
                        }
                    }

                    // KPI Cards Row 2
                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            KpiCard(
                                title = "This Month",
                                value = formatCurrency(summary.salesThisMonth),
                                modifier = Modifier.weight(1f)
                            )
                            KpiCard(
                                title = "Stock Value",
                                value = formatCurrency(summary.totalStockValue),
                                modifier = Modifier.weight(1f)
                            )
                        }
                    }

                    // Stock Alert Cards
                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Card(
                                modifier = Modifier
                                    .weight(1f)
                                    .clickable { onNavigateToInventory() },
                                colors = CardDefaults.cardColors(
                                    containerColor = StockOutColor.copy(alpha = 0.05f)
                                )
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    Text(
                                        text = "Out of Stock",
                                        style = MaterialTheme.typography.bodySmall,
                                        color = StockOutColor
                                    )
                                    Text(
                                        text = "${summary.outOfStockCount}",
                                        style = MaterialTheme.typography.headlineMedium,
                                        fontWeight = FontWeight.Bold,
                                        color = StockOutColor
                                    )
                                }
                            }
                            Card(
                                modifier = Modifier
                                    .weight(1f)
                                    .clickable { onNavigateToInventory() },
                                colors = CardDefaults.cardColors(
                                    containerColor = StockLowColor.copy(alpha = 0.05f)
                                )
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    Text(
                                        text = "Low Stock",
                                        style = MaterialTheme.typography.bodySmall,
                                        color = StockLowColor
                                    )
                                    Text(
                                        text = "${summary.lowStockCount}",
                                        style = MaterialTheme.typography.headlineMedium,
                                        fontWeight = FontWeight.Bold,
                                        color = StockLowColor
                                    )
                                }
                            }
                        }
                    }

                    // Sales Trend Chart
                    item {
                        val trendData = salesTrend
                        val chartEntries = trendData?.values?.mapIndexed { index, value ->
                            Entry(index.toFloat(), value.toFloat())
                        } ?: emptyList()
                        val chartLabels = trendData?.labels ?: emptyList()

                        SalesTrendChart(
                            entries = chartEntries,
                            labels = chartLabels,
                            selectedPeriod = selectedPeriod,
                            onPeriodSelected = { period ->
                                viewModel.loadSalesTrend(period)
                            }
                        )
                    }

                    // Top Products Today
                    if (!summary.topProductsToday.isNullOrEmpty()) {
                        item {
                            Text(
                                text = "Top Products Today",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.SemiBold
                            )
                        }
                        item {
                            Card(
                                colors = CardDefaults.cardColors(
                                    containerColor = MaterialTheme.colorScheme.surface
                                ),
                                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                            ) {
                                Column(modifier = Modifier.padding(12.dp)) {
                                    summary.topProductsToday.forEachIndexed { index, product ->
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(vertical = 8.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Row(
                                                modifier = Modifier.weight(1f),
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Text(
                                                    text = "${index + 1}.",
                                                    style = MaterialTheme.typography.bodyMedium,
                                                    fontWeight = FontWeight.Bold,
                                                    color = MaterialTheme.colorScheme.primary
                                                )
                                                Spacer(modifier = Modifier.width(8.dp))
                                                Column {
                                                    Text(
                                                        text = product.name,
                                                        style = MaterialTheme.typography.bodyMedium
                                                    )
                                                    Text(
                                                        text = "${"%.0f".format(product.unitsSold)} sold",
                                                        style = MaterialTheme.typography.bodySmall,
                                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                                    )
                                                }
                                            }
                                            Text(
                                                text = formatCurrency(product.revenue),
                                                style = MaterialTheme.typography.bodyMedium,
                                                fontWeight = FontWeight.SemiBold
                                            )
                                        }
                                        if (index < summary.topProductsToday.size - 1) {
                                            HorizontalDivider()
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Recent Sales
                    if (!summary.recentSales.isNullOrEmpty()) {
                        item {
                            Text(
                                text = "Recent Sales",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.SemiBold
                            )
                        }
                        items(summary.recentSales) { sale ->
                            Card(
                                colors = CardDefaults.cardColors(
                                    containerColor = MaterialTheme.colorScheme.surface
                                ),
                                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                            ) {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(12.dp),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Column(modifier = Modifier.weight(1f)) {
                                        Text(
                                            text = sale.saleNumber,
                                            style = MaterialTheme.typography.bodyMedium,
                                            fontWeight = FontWeight.SemiBold
                                        )
                                        Text(
                                            text = sale.customerName ?: "Walk-in Customer",
                                            style = MaterialTheme.typography.bodySmall,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                    Column(horizontalAlignment = Alignment.End) {
                                        Text(
                                            text = formatCurrency(sale.totalAmount),
                                            style = MaterialTheme.typography.bodyMedium,
                                            fontWeight = FontWeight.Bold
                                        )
                                        PaymentMethodBadge(method = sale.paymentMethod)
                                    }
                                }
                            }
                        }
                    }

                    // Bottom spacer for FAB
                    item {
                        Spacer(modifier = Modifier.height(72.dp))
                    }
                }
            }
        }
    }
}
