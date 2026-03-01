package com.kinarahub.ui.screens.sales

import com.kinarahub.data.remote.models.Product
import org.junit.Assert.assertEquals
import org.junit.Test

class CartCalculationTest {

    private fun makeProduct(id: Int, name: String, sellingPrice: Double) = Product(
        id = id,
        storeId = 1,
        sku = "SKU-$id",
        name = name,
        categoryId = null,
        categoryName = null,
        uomId = null,
        uomName = null,
        uomAbbreviation = null,
        sellingPrice = sellingPrice,
        costPrice = null,
        stockQuantity = 100.0,
        reorderPoint = 10.0,
        status = "active",
        stockStatus = "in_stock",
        variants = null,
        version = 0,
        createdAt = null,
        updatedAt = null
    )

    // --- Exact scenario from spec: 3 items, remove, update ---

    @Test
    fun `three item cart line totals are correct`() {
        // (qty=2, price=100), (qty=1.5, price=200), (qty=0.5, price=50)
        val item1 = CartItem(product = makeProduct(1, "A", 100.0), unitPrice = 100.0, quantity = 2.0)
        val item2 = CartItem(product = makeProduct(2, "B", 200.0), unitPrice = 200.0, quantity = 1.5)
        val item3 = CartItem(product = makeProduct(3, "C", 50.0), unitPrice = 50.0, quantity = 0.5)

        assertEquals(200.0, item1.lineTotal, 0.001)   // 2 * 100
        assertEquals(300.0, item2.lineTotal, 0.001)   // 1.5 * 200
        assertEquals(25.0, item3.lineTotal, 0.001)    // 0.5 * 50
    }

    @Test
    fun `three item cart subtotal is 525`() {
        val cart = listOf(
            CartItem(product = makeProduct(1, "A", 100.0), unitPrice = 100.0, quantity = 2.0),
            CartItem(product = makeProduct(2, "B", 200.0), unitPrice = 200.0, quantity = 1.5),
            CartItem(product = makeProduct(3, "C", 50.0), unitPrice = 50.0, quantity = 0.5)
        )
        val state = POSUiState(cart = cart)

        // 200 + 300 + 25 = 525
        assertEquals(525.0, state.subtotal, 0.001)
    }

    @Test
    fun `removing middle item gives subtotal of 225`() {
        val cart = mutableListOf(
            CartItem(product = makeProduct(1, "A", 100.0), unitPrice = 100.0, quantity = 2.0),
            CartItem(product = makeProduct(2, "B", 200.0), unitPrice = 200.0, quantity = 1.5),
            CartItem(product = makeProduct(3, "C", 50.0), unitPrice = 50.0, quantity = 0.5)
        )

        // Remove middle item (index 1)
        cart.removeAt(1)
        val state = POSUiState(cart = cart)

        // 200 + 25 = 225
        assertEquals(225.0, state.subtotal, 0.001)
        assertEquals(2, state.cartItemCount)
    }

    @Test
    fun `updating first item qty to 3 after removing middle gives subtotal of 325`() {
        val cart = mutableListOf(
            CartItem(product = makeProduct(1, "A", 100.0), unitPrice = 100.0, quantity = 2.0),
            CartItem(product = makeProduct(2, "B", 200.0), unitPrice = 200.0, quantity = 1.5),
            CartItem(product = makeProduct(3, "C", 50.0), unitPrice = 50.0, quantity = 0.5)
        )

        // Remove middle item
        cart.removeAt(1)

        // Update first item qty from 2 to 3
        cart[0] = cart[0].copy(quantity = 3.0)
        val state = POSUiState(cart = cart)

        // (3 * 100) + (0.5 * 50) = 300 + 25 = 325
        assertEquals(325.0, state.subtotal, 0.001)
    }

    // --- Additional edge cases ---

    @Test
    fun `single item line total equals quantity times unit price`() {
        val item = CartItem(
            product = makeProduct(1, "Widget", 150.0),
            unitPrice = 150.0,
            quantity = 3.0
        )
        assertEquals(450.0, item.lineTotal, 0.001)
    }

    @Test
    fun `default quantity of 1 gives line total equal to unit price`() {
        val item = CartItem(
            product = makeProduct(1, "Widget", 250.0),
            unitPrice = 250.0
        )
        assertEquals(250.0, item.lineTotal, 0.001)
    }

    @Test
    fun `empty cart has zero subtotal and total`() {
        val state = POSUiState(cart = emptyList())
        assertEquals(0.0, state.subtotal, 0.001)
        assertEquals(0.0, state.total, 0.001)
        assertEquals(0, state.cartItemCount)
    }

    @Test
    fun `cart total equals subtotal when no tax`() {
        val cart = listOf(
            CartItem(product = makeProduct(1, "A", 100.0), unitPrice = 100.0, quantity = 2.0),
            CartItem(product = makeProduct(2, "B", 200.0), unitPrice = 200.0, quantity = 1.5),
            CartItem(product = makeProduct(3, "C", 50.0), unitPrice = 50.0, quantity = 0.5)
        )
        val state = POSUiState(cart = cart)

        assertEquals(state.subtotal, state.total, 0.001)
    }

    @Test
    fun `variant in cart uses variant unit price not product price`() {
        val item = CartItem(
            product = makeProduct(1, "Shirt", 500.0),
            variantId = 10,
            variantName = "Red / Large",
            unitPrice = 550.0,
            quantity = 2.0
        )
        assertEquals(1100.0, item.lineTotal, 0.001)
    }

    @Test
    fun `large cart of 20 items calculates correctly`() {
        val cart = (1..20).map { i ->
            CartItem(
                product = makeProduct(i, "Product $i", 10.0 * i),
                unitPrice = 10.0 * i,
                quantity = i.toDouble()
            )
        }
        val state = POSUiState(cart = cart)

        // Sum of i * (10*i) = 10 * Sum(i^2) for i=1..20
        // Sum(i^2) for 1..20 = 20*21*41/6 = 2870
        // Total = 10 * 2870 = 28700
        assertEquals(28700.0, state.total, 0.001)
        assertEquals(20, state.cartItemCount)
    }
}
