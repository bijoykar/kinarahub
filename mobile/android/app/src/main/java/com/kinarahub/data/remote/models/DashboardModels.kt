package com.kinarahub.data.remote.models

import com.google.gson.annotations.SerializedName

data class DashboardSummary(
    @SerializedName("sales_today")
    val salesToday: Double,
    @SerializedName("sales_yesterday")
    val salesYesterday: Double,
    @SerializedName("sales_today_change")
    val salesTodayChange: Double?,
    @SerializedName("sales_this_week")
    val salesThisWeek: Double,
    @SerializedName("sales_this_month")
    val salesThisMonth: Double,
    @SerializedName("total_stock_value")
    val totalStockValue: Double,
    @SerializedName("out_of_stock_count")
    val outOfStockCount: Int,
    @SerializedName("low_stock_count")
    val lowStockCount: Int,
    @SerializedName("top_products_today")
    val topProductsToday: List<TopProduct>?,
    @SerializedName("recent_sales")
    val recentSales: List<Sale>?,
    @SerializedName("sales_trend")
    val salesTrend: SalesTrend?
)

data class TopProduct(
    val name: String,
    @SerializedName("units_sold")
    val unitsSold: Double,
    val revenue: Double
)

data class SalesTrend(
    val labels: List<String>,
    val values: List<Double>
)
