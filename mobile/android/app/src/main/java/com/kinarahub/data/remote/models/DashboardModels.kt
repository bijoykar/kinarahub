package com.kinarahub.data.remote.models

import com.google.gson.annotations.SerializedName

data class DashboardSummary(
    @SerializedName("today_revenue")
    val todayRevenue: Double,
    @SerializedName("yesterday_revenue")
    val yesterdayRevenue: Double,
    @SerializedName("percent_change")
    val percentChange: Double?,
    @SerializedName("week_revenue")
    val weekRevenue: Double,
    @SerializedName("month_revenue")
    val monthRevenue: Double,
    @SerializedName("stock_value")
    val stockValue: Double,
    @SerializedName("out_of_stock")
    val outOfStockCount: Int,
    @SerializedName("low_stock")
    val lowStockCount: Int,
    @SerializedName("top_products")
    val topProducts: List<TopProduct>?,
    @SerializedName("recent_sales")
    val recentSales: List<RecentSale>?,
    @SerializedName("sales_trend")
    val salesTrend: ChartData?,
    @SerializedName("payment_breakdown")
    val paymentBreakdown: ChartData?,
    @SerializedName("stock_distribution")
    val stockDistribution: StockDistribution?
)

data class TopProduct(
    @SerializedName("product_name")
    val productName: String,
    @SerializedName("units_sold")
    val unitsSold: Double,
    val revenue: Double
)

data class RecentSale(
    @SerializedName("sale_number")
    val saleNumber: String,
    @SerializedName("sale_date")
    val saleDate: String,
    @SerializedName("payment_method")
    val paymentMethod: String,
    @SerializedName("total_amount")
    val totalAmount: Double,
    @SerializedName("customer_name")
    val customerName: String?
)

/**
 * Generic chart data returned by /dashboard/chart and embedded in /dashboard summary.
 * Backend uses { labels: [], amounts: [] } for sales_trend and payment_breakdown.
 */
data class ChartData(
    val labels: List<String>,
    val amounts: List<Double>?
)

/**
 * Stock distribution chart data: { labels: [], counts: [] }
 */
data class StockDistribution(
    val labels: List<String>,
    val counts: List<Int>
)
