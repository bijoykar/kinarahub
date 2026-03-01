package com.kinarahub.ui.screens.inventory

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import com.kinarahub.ui.components.*

@Composable
fun ProductDetailScreen(
    productId: Int,
    viewModel: ProductDetailViewModel = hiltViewModel(),
    onBack: () -> Unit
) {
    LaunchedEffect(productId) {
        viewModel.loadProduct(productId)
    }

    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            KinaraTopBar(title = "Product Detail", onBack = onBack)
        }
    ) { padding ->
        when (val state = uiState) {
            is ProductDetailUiState.Loading -> LoadingState(modifier = Modifier.padding(padding))
            is ProductDetailUiState.Error -> ErrorState(
                message = state.message,
                onRetry = { viewModel.loadProduct(productId) },
                modifier = Modifier.padding(padding)
            )
            is ProductDetailUiState.Success -> {
                val product = state.product
                LazyColumn(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(padding),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    // Product Header
                    item {
                        Card(
                            colors = CardDefaults.cardColors(
                                containerColor = MaterialTheme.colorScheme.surface
                            ),
                            elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                        ) {
                            Column(modifier = Modifier.padding(16.dp)) {
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceBetween
                                ) {
                                    Text(
                                        text = product.name,
                                        style = MaterialTheme.typography.headlineSmall,
                                        fontWeight = FontWeight.Bold,
                                        modifier = Modifier.weight(1f)
                                    )
                                    StockBadge(stockStatus = product.stockStatus)
                                }
                                Spacer(modifier = Modifier.height(8.dp))
                                Text(
                                    text = "SKU: ${product.sku}",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }
                    }

                    // Details Card
                    item {
                        Card(
                            colors = CardDefaults.cardColors(
                                containerColor = MaterialTheme.colorScheme.surface
                            ),
                            elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                        ) {
                            Column(modifier = Modifier.padding(16.dp)) {
                                Text(
                                    text = "Details",
                                    style = MaterialTheme.typography.titleMedium,
                                    fontWeight = FontWeight.SemiBold
                                )
                                Spacer(modifier = Modifier.height(12.dp))
                                DetailRow("Category", product.categoryName ?: "-")
                                DetailRow("Unit", product.uomName ?: "-")
                                DetailRow("Selling Price", formatCurrency(product.sellingPrice))
                                if (product.costPrice != null) {
                                    DetailRow("Cost Price", formatCurrency(product.costPrice))
                                }
                                DetailRow("Stock Quantity",
                                    "${"%.1f".format(product.stockQuantity)} ${product.uomAbbreviation ?: ""}")
                                DetailRow("Reorder Point",
                                    "${"%.1f".format(product.reorderPoint)} ${product.uomAbbreviation ?: ""}")
                                DetailRow("Status", product.status.replaceFirstChar { it.uppercase() })
                            }
                        }
                    }

                    // Variants
                    if (!product.variants.isNullOrEmpty()) {
                        item {
                            Text(
                                text = "Variants",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.SemiBold
                            )
                        }
                        items(product.variants) { variant ->
                            Card(
                                colors = CardDefaults.cardColors(
                                    containerColor = MaterialTheme.colorScheme.surface
                                ),
                                elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                            ) {
                                Column(modifier = Modifier.padding(12.dp)) {
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.SpaceBetween
                                    ) {
                                        Text(
                                            text = variant.variantName,
                                            style = MaterialTheme.typography.bodyLarge,
                                            fontWeight = FontWeight.SemiBold
                                        )
                                        if (variant.stockStatus != null) {
                                            StockBadge(stockStatus = variant.stockStatus)
                                        }
                                    }
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Text(
                                        text = "SKU: ${variant.sku}",
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.SpaceBetween
                                    ) {
                                        Text(
                                            text = formatCurrency(variant.sellingPrice),
                                            style = MaterialTheme.typography.bodyMedium,
                                            fontWeight = FontWeight.Bold,
                                            color = MaterialTheme.colorScheme.primary
                                        )
                                        Text(
                                            text = "Qty: ${"%.1f".format(variant.stockQuantity)}",
                                            style = MaterialTheme.typography.bodyMedium
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun DetailRow(label: String, value: String) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(
            text = label,
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.Medium
        )
    }
}
