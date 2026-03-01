package com.kinarahub.util

object StockStatusUtil {

    const val IN_STOCK = "in_stock"
    const val LOW_STOCK = "low_stock"
    const val OUT_OF_STOCK = "out_of_stock"

    /**
     * Computes stock status from quantity and reorder point.
     *
     * Rules (from spec.md):
     * - qty == 0        -> OUT_OF_STOCK (red)
     * - 0 < qty <= reorder_point -> LOW_STOCK (amber)
     * - qty > reorder_point      -> IN_STOCK (green)
     */
    fun computeStockStatus(stockQuantity: Double, reorderPoint: Double): String {
        return when {
            stockQuantity <= 0.0 -> OUT_OF_STOCK
            stockQuantity <= reorderPoint -> LOW_STOCK
            else -> IN_STOCK
        }
    }
}
