package com.kinarahub.data.remote.models

import com.google.gson.annotations.SerializedName

data class Sale(
    val id: Int,
    @SerializedName("store_id")
    val storeId: Int,
    @SerializedName("sale_number")
    val saleNumber: String,
    @SerializedName("sale_date")
    val saleDate: String,
    @SerializedName("entry_mode")
    val entryMode: String,
    @SerializedName("customer_id")
    val customerId: Int?,
    @SerializedName("customer_name")
    val customerName: String?,
    @SerializedName("payment_method")
    val paymentMethod: String,
    val subtotal: Double,
    @SerializedName("tax_amount")
    val taxAmount: Double,
    @SerializedName("total_amount")
    val totalAmount: Double,
    val notes: String?,
    val items: List<SaleItem>?,
    @SerializedName("created_by")
    val createdBy: Int?,
    @SerializedName("created_at")
    val createdAt: String?
)

data class SaleItem(
    val id: Int,
    @SerializedName("product_id")
    val productId: Int,
    @SerializedName("variant_id")
    val variantId: Int?,
    @SerializedName("product_name_snapshot")
    val productNameSnapshot: String,
    @SerializedName("sku_snapshot")
    val skuSnapshot: String,
    val quantity: Double,
    @SerializedName("unit_price")
    val unitPrice: Double,
    @SerializedName("line_total")
    val lineTotal: Double
)

data class CreateSaleRequest(
    @SerializedName("entry_mode")
    val entryMode: String = "pos",
    @SerializedName("customer_id")
    val customerId: Int?,
    @SerializedName("payment_method")
    val paymentMethod: String,
    val items: List<CreateSaleItemRequest>,
    val notes: String? = null
)

data class CreateSaleItemRequest(
    @SerializedName("product_id")
    val productId: Int,
    @SerializedName("variant_id")
    val variantId: Int? = null,
    val quantity: Double,
    @SerializedName("unit_price")
    val unitPrice: Double
)
