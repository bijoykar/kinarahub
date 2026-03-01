package com.kinarahub.util

import org.junit.Assert.assertEquals
import org.junit.Test

class StockStatusUtilTest {

    @Test
    fun `qty zero returns OUT_OF_STOCK`() {
        val result = StockStatusUtil.computeStockStatus(
            stockQuantity = 0.0,
            reorderPoint = 10.0
        )
        assertEquals(StockStatusUtil.OUT_OF_STOCK, result)
    }

    @Test
    fun `negative qty returns OUT_OF_STOCK`() {
        // Edge case: should never happen but defensive
        val result = StockStatusUtil.computeStockStatus(
            stockQuantity = -5.0,
            reorderPoint = 10.0
        )
        assertEquals(StockStatusUtil.OUT_OF_STOCK, result)
    }

    @Test
    fun `qty between zero and reorder point returns LOW_STOCK`() {
        val result = StockStatusUtil.computeStockStatus(
            stockQuantity = 5.0,
            reorderPoint = 10.0
        )
        assertEquals(StockStatusUtil.LOW_STOCK, result)
    }

    @Test
    fun `qty equal to reorder point returns LOW_STOCK`() {
        // Boundary: qty == reorder_point is LOW_STOCK per spec (0 < qty <= reorder_point)
        val result = StockStatusUtil.computeStockStatus(
            stockQuantity = 10.0,
            reorderPoint = 10.0
        )
        assertEquals(StockStatusUtil.LOW_STOCK, result)
    }

    @Test
    fun `qty above reorder point returns IN_STOCK`() {
        val result = StockStatusUtil.computeStockStatus(
            stockQuantity = 50.0,
            reorderPoint = 10.0
        )
        assertEquals(StockStatusUtil.IN_STOCK, result)
    }

    @Test
    fun `qty just above reorder point returns IN_STOCK`() {
        // Boundary: qty = reorder_point + 0.001
        val result = StockStatusUtil.computeStockStatus(
            stockQuantity = 10.001,
            reorderPoint = 10.0
        )
        assertEquals(StockStatusUtil.IN_STOCK, result)
    }

    @Test
    fun `fractional qty below reorder returns LOW_STOCK`() {
        // Spec supports DECIMAL(10,3) for stock
        val result = StockStatusUtil.computeStockStatus(
            stockQuantity = 0.5,
            reorderPoint = 1.0
        )
        assertEquals(StockStatusUtil.LOW_STOCK, result)
    }

    @Test
    fun `qty equals reorder at fractional boundary returns LOW_STOCK`() {
        // Boundary: qty == reorder_point at 0.5, spec says 0 < qty <= reorder -> LOW_STOCK
        val result = StockStatusUtil.computeStockStatus(
            stockQuantity = 0.5,
            reorderPoint = 0.5
        )
        assertEquals(StockStatusUtil.LOW_STOCK, result)
    }

    @Test
    fun `tiny qty with zero reorder returns IN_STOCK`() {
        // reorder_point = 0 means no threshold; any positive qty is IN_STOCK
        val result = StockStatusUtil.computeStockStatus(
            stockQuantity = 0.001,
            reorderPoint = 0.0
        )
        assertEquals(StockStatusUtil.IN_STOCK, result)
    }

    @Test
    fun `zero reorder point with nonzero qty returns IN_STOCK`() {
        val result = StockStatusUtil.computeStockStatus(
            stockQuantity = 1.0,
            reorderPoint = 0.0
        )
        assertEquals(StockStatusUtil.IN_STOCK, result)
    }

    @Test
    fun `zero qty with zero reorder point returns OUT_OF_STOCK`() {
        val result = StockStatusUtil.computeStockStatus(
            stockQuantity = 0.0,
            reorderPoint = 0.0
        )
        assertEquals(StockStatusUtil.OUT_OF_STOCK, result)
    }
}
