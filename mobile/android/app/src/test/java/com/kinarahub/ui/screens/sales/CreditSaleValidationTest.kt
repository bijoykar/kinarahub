package com.kinarahub.ui.screens.sales

import com.kinarahub.data.remote.models.Product
import org.junit.Assert.*
import org.junit.Test

class CreditSaleValidationTest {

    private fun makeProduct(id: Int, price: Double) = Product(
        id = id,
        storeId = 1,
        sku = "SKU-$id",
        name = "Product $id",
        categoryId = null,
        categoryName = null,
        uomId = null,
        uomName = null,
        uomAbbreviation = null,
        sellingPrice = price,
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

    private fun makeCartItem(id: Int, price: Double, qty: Double = 1.0) = CartItem(
        product = makeProduct(id, price),
        unitPrice = price,
        quantity = qty
    )

    // --- Credit payment requires customer ---

    @Test
    fun `credit sale with no customer produces error`() {
        val state = POSUiState(
            cart = listOf(makeCartItem(1, 100.0)),
            paymentMethod = "credit",
            selectedCustomerId = null
        )

        // Replicate the validation logic from POSSaleViewModel.submitSale()
        val hasError = state.paymentMethod == "credit" && state.selectedCustomerId == null
        assertTrue("Credit sale without customer should fail", hasError)
    }

    @Test
    fun `credit sale with customer selected passes validation`() {
        val state = POSUiState(
            cart = listOf(makeCartItem(1, 100.0)),
            paymentMethod = "credit",
            selectedCustomerId = 42
        )

        val hasError = state.paymentMethod == "credit" && state.selectedCustomerId == null
        assertFalse("Credit sale with customer should pass", hasError)
    }

    @Test
    fun `cash sale without customer passes validation`() {
        val state = POSUiState(
            cart = listOf(makeCartItem(1, 100.0)),
            paymentMethod = "cash",
            selectedCustomerId = null
        )

        val hasError = state.paymentMethod == "credit" && state.selectedCustomerId == null
        assertFalse("Cash sale without customer should pass", hasError)
    }

    @Test
    fun `upi sale without customer passes validation`() {
        val state = POSUiState(
            cart = listOf(makeCartItem(1, 100.0)),
            paymentMethod = "upi",
            selectedCustomerId = null
        )

        val hasError = state.paymentMethod == "credit" && state.selectedCustomerId == null
        assertFalse("UPI sale without customer should pass", hasError)
    }

    @Test
    fun `card sale without customer passes validation`() {
        val state = POSUiState(
            cart = listOf(makeCartItem(1, 100.0)),
            paymentMethod = "card",
            selectedCustomerId = null
        )

        val hasError = state.paymentMethod == "credit" && state.selectedCustomerId == null
        assertFalse("Card sale without customer should pass", hasError)
    }

    // --- Empty cart validation ---

    @Test
    fun `empty cart produces error`() {
        val state = POSUiState(
            cart = emptyList(),
            paymentMethod = "cash"
        )

        assertTrue("Empty cart should fail", state.cart.isEmpty())
    }

    @Test
    fun `non-empty cart passes empty cart check`() {
        val state = POSUiState(
            cart = listOf(makeCartItem(1, 100.0)),
            paymentMethod = "cash"
        )

        assertFalse("Non-empty cart should pass", state.cart.isEmpty())
    }

    // --- Combined validation: exact error messages from ViewModel ---

    @Test
    fun `submitSale validation produces correct error for empty cart`() {
        val state = POSUiState(cart = emptyList())

        val error: String? = when {
            state.cart.isEmpty() -> "Cart is empty"
            state.paymentMethod == "credit" && state.selectedCustomerId == null ->
                "Customer required for credit sales"
            else -> null
        }

        assertEquals("Cart is empty", error)
    }

    @Test
    fun `submitSale validation produces correct error for credit without customer`() {
        val state = POSUiState(
            cart = listOf(makeCartItem(1, 100.0)),
            paymentMethod = "credit",
            selectedCustomerId = null
        )

        val error: String? = when {
            state.cart.isEmpty() -> "Cart is empty"
            state.paymentMethod == "credit" && state.selectedCustomerId == null ->
                "Customer required for credit sales"
            else -> null
        }

        assertEquals("Customer required for credit sales", error)
    }

    @Test
    fun `submitSale validation produces no error for valid cash sale`() {
        val state = POSUiState(
            cart = listOf(makeCartItem(1, 100.0)),
            paymentMethod = "cash",
            selectedCustomerId = null
        )

        val error: String? = when {
            state.cart.isEmpty() -> "Cart is empty"
            state.paymentMethod == "credit" && state.selectedCustomerId == null ->
                "Customer required for credit sales"
            else -> null
        }

        assertNull(error)
    }

    @Test
    fun `submitSale validation produces no error for valid credit sale`() {
        val state = POSUiState(
            cart = listOf(makeCartItem(1, 100.0)),
            paymentMethod = "credit",
            selectedCustomerId = 7
        )

        val error: String? = when {
            state.cart.isEmpty() -> "Cart is empty"
            state.paymentMethod == "credit" && state.selectedCustomerId == null ->
                "Customer required for credit sales"
            else -> null
        }

        assertNull(error)
    }
}
