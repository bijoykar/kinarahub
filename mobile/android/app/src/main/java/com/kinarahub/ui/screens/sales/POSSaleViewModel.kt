package com.kinarahub.ui.screens.sales

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.kinarahub.data.remote.ApiService
import com.kinarahub.data.remote.models.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class CartItem(
    val product: Product,
    val variantId: Int? = null,
    val variantName: String? = null,
    val quantity: Double = 1.0,
    val unitPrice: Double
) {
    val lineTotal: Double get() = quantity * unitPrice
}

data class POSUiState(
    val searchQuery: String = "",
    val searchResults: List<Product> = emptyList(),
    val isSearching: Boolean = false,
    val cart: List<CartItem> = emptyList(),
    val paymentMethod: String = "cash",
    val selectedCustomerId: Int? = null,
    val customers: List<Customer> = emptyList(),
    val isSubmitting: Boolean = false,
    val error: String? = null,
    val completedSaleId: Int? = null
) {
    val subtotal: Double get() = cart.sumOf { it.lineTotal }
    val total: Double get() = subtotal
    val cartItemCount: Int get() = cart.size
}

@HiltViewModel
class POSSaleViewModel @Inject constructor(
    private val apiService: ApiService
) : ViewModel() {

    private val _uiState = MutableStateFlow(POSUiState())
    val uiState: StateFlow<POSUiState> = _uiState.asStateFlow()

    fun onSearchChange(query: String) {
        _uiState.value = _uiState.value.copy(searchQuery = query)
        if (query.length >= 2) {
            searchProducts(query)
        } else {
            _uiState.value = _uiState.value.copy(searchResults = emptyList())
        }
    }

    private fun searchProducts(query: String) {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isSearching = true)
            try {
                val response = apiService.getProducts(search = query, perPage = 10)
                if (response.isSuccessful && response.body()?.success == true) {
                    _uiState.value = _uiState.value.copy(
                        searchResults = response.body()?.data ?: emptyList(),
                        isSearching = false
                    )
                } else {
                    _uiState.value = _uiState.value.copy(isSearching = false)
                }
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(isSearching = false)
            }
        }
    }

    fun addToCart(product: Product, variantId: Int? = null, variantName: String? = null) {
        val currentCart = _uiState.value.cart.toMutableList()
        val existingIndex = currentCart.indexOfFirst {
            it.product.id == product.id && it.variantId == variantId
        }

        if (existingIndex >= 0) {
            val existing = currentCart[existingIndex]
            currentCart[existingIndex] = existing.copy(quantity = existing.quantity + 1)
        } else {
            currentCart.add(
                CartItem(
                    product = product,
                    variantId = variantId,
                    variantName = variantName,
                    quantity = 1.0,
                    unitPrice = product.sellingPrice
                )
            )
        }

        _uiState.value = _uiState.value.copy(
            cart = currentCart,
            searchQuery = "",
            searchResults = emptyList()
        )
    }

    fun updateCartItemQuantity(index: Int, quantity: Double) {
        val currentCart = _uiState.value.cart.toMutableList()
        if (index in currentCart.indices) {
            if (quantity <= 0) {
                currentCart.removeAt(index)
            } else {
                currentCart[index] = currentCart[index].copy(quantity = quantity)
            }
            _uiState.value = _uiState.value.copy(cart = currentCart)
        }
    }

    fun removeCartItem(index: Int) {
        val currentCart = _uiState.value.cart.toMutableList()
        if (index in currentCart.indices) {
            currentCart.removeAt(index)
            _uiState.value = _uiState.value.copy(cart = currentCart)
        }
    }

    fun setPaymentMethod(method: String) {
        _uiState.value = _uiState.value.copy(paymentMethod = method, error = null)
        if (method == "credit") {
            loadCustomers()
        }
    }

    fun setCustomer(customerId: Int?) {
        _uiState.value = _uiState.value.copy(selectedCustomerId = customerId)
    }

    private fun loadCustomers() {
        viewModelScope.launch {
            try {
                val response = apiService.getCustomers(perPage = 100)
                if (response.isSuccessful && response.body()?.success == true) {
                    _uiState.value = _uiState.value.copy(
                        customers = response.body()?.data?.filter { it.isDefault != 1 }
                            ?: emptyList()
                    )
                }
            } catch (_: Exception) { }
        }
    }

    fun submitSale() {
        val state = _uiState.value

        if (state.cart.isEmpty()) {
            _uiState.value = state.copy(error = "Cart is empty")
            return
        }

        if (state.paymentMethod == "credit" && state.selectedCustomerId == null) {
            _uiState.value = state.copy(error = "Customer required for credit sales")
            return
        }

        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isSubmitting = true, error = null)
            try {
                val request = CreateSaleRequest(
                    entryMode = "pos",
                    customerId = state.selectedCustomerId,
                    paymentMethod = state.paymentMethod,
                    items = state.cart.map { item ->
                        CreateSaleItemRequest(
                            productId = item.product.id,
                            variantId = item.variantId,
                            quantity = item.quantity,
                            unitPrice = item.unitPrice
                        )
                    }
                )

                val response = apiService.createSale(request)
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true && body.data != null) {
                        _uiState.value = _uiState.value.copy(
                            isSubmitting = false,
                            completedSaleId = body.data.id
                        )
                    } else {
                        _uiState.value = _uiState.value.copy(
                            isSubmitting = false,
                            error = body?.error ?: "Failed to create sale"
                        )
                    }
                } else {
                    val errorMsg = when (response.code()) {
                        409 -> "Stock conflict. A product was modified. Please refresh and try again."
                        422 -> "Invalid sale data. Please check your cart."
                        else -> "Failed to create sale (${response.code()})"
                    }
                    _uiState.value = _uiState.value.copy(
                        isSubmitting = false,
                        error = errorMsg
                    )
                }
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isSubmitting = false,
                    error = "Network error. Please check your connection."
                )
            }
        }
    }
}
