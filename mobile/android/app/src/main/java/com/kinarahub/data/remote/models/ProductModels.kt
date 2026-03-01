package com.kinarahub.data.remote.models

import com.google.gson.annotations.SerializedName

data class Product(
    val id: Int,
    @SerializedName("store_id")
    val storeId: Int,
    val sku: String,
    val name: String,
    @SerializedName("category_id")
    val categoryId: Int?,
    @SerializedName("category_name")
    val categoryName: String?,
    @SerializedName("uom_id")
    val uomId: Int?,
    @SerializedName("uom_name")
    val uomName: String?,
    @SerializedName("uom_abbreviation")
    val uomAbbreviation: String?,
    @SerializedName("selling_price")
    val sellingPrice: Double,
    @SerializedName("cost_price")
    val costPrice: Double?,
    @SerializedName("stock_quantity")
    val stockQuantity: Double,
    @SerializedName("reorder_point")
    val reorderPoint: Double,
    val status: String,
    @SerializedName("stock_status")
    val stockStatus: String,
    val variants: List<ProductVariant>?,
    val version: Int,
    @SerializedName("created_at")
    val createdAt: String?,
    @SerializedName("updated_at")
    val updatedAt: String?
)

data class ProductVariant(
    val id: Int,
    @SerializedName("product_id")
    val productId: Int,
    @SerializedName("variant_name")
    val variantName: String,
    val sku: String,
    @SerializedName("selling_price")
    val sellingPrice: Double,
    @SerializedName("cost_price")
    val costPrice: Double?,
    @SerializedName("stock_quantity")
    val stockQuantity: Double,
    @SerializedName("reorder_point")
    val reorderPoint: Double,
    @SerializedName("stock_status")
    val stockStatus: String?,
    val version: Int
)

data class CreateProductRequest(
    val sku: String,
    val name: String,
    @SerializedName("category_id")
    val categoryId: Int?,
    @SerializedName("uom_id")
    val uomId: Int?,
    @SerializedName("selling_price")
    val sellingPrice: Double,
    @SerializedName("cost_price")
    val costPrice: Double?,
    @SerializedName("stock_quantity")
    val stockQuantity: Double,
    @SerializedName("reorder_point")
    val reorderPoint: Double
)

data class UpdateProductRequest(
    val sku: String?,
    val name: String?,
    @SerializedName("category_id")
    val categoryId: Int?,
    @SerializedName("uom_id")
    val uomId: Int?,
    @SerializedName("selling_price")
    val sellingPrice: Double?,
    @SerializedName("cost_price")
    val costPrice: Double?,
    @SerializedName("stock_quantity")
    val stockQuantity: Double?,
    @SerializedName("reorder_point")
    val reorderPoint: Double?,
    val version: Int
)

data class CreateProductResponse(
    @SerializedName("product_id")
    val productId: Int
)
