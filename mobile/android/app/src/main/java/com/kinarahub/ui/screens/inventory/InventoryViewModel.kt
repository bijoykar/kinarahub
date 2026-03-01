package com.kinarahub.ui.screens.inventory

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.kinarahub.data.remote.ApiService
import com.kinarahub.data.remote.models.Meta
import com.kinarahub.data.remote.models.Product
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class InventoryUiState(
    val products: List<Product> = emptyList(),
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val error: String? = null,
    val searchQuery: String = "",
    val currentPage: Int = 1,
    val meta: Meta? = null,
    val hasMore: Boolean = true
)

@HiltViewModel
class InventoryViewModel @Inject constructor(
    private val apiService: ApiService
) : ViewModel() {

    private val _uiState = MutableStateFlow(InventoryUiState())
    val uiState: StateFlow<InventoryUiState> = _uiState.asStateFlow()

    init {
        loadProducts()
    }

    fun loadProducts(page: Int = 1) {
        viewModelScope.launch {
            val isFirstPage = page == 1
            _uiState.value = _uiState.value.copy(
                isLoading = isFirstPage,
                isLoadingMore = !isFirstPage,
                error = null
            )

            try {
                val response = apiService.getProducts(
                    page = page,
                    search = _uiState.value.searchQuery.ifBlank { null }
                )

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true && body.data != null) {
                        val currentProducts = if (isFirstPage) emptyList()
                        else _uiState.value.products
                        val meta = body.meta
                        val hasMore = meta != null && (meta.page * meta.perPage) < meta.total

                        _uiState.value = _uiState.value.copy(
                            products = currentProducts + body.data,
                            isLoading = false,
                            isLoadingMore = false,
                            currentPage = page,
                            meta = meta,
                            hasMore = hasMore
                        )
                    } else {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            isLoadingMore = false,
                            error = body?.error ?: "Failed to load products"
                        )
                    }
                } else {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        isLoadingMore = false,
                        error = "Failed to load products (${response.code()})"
                    )
                }
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    isLoadingMore = false,
                    error = "Network error. Please check your connection."
                )
            }
        }
    }

    fun onSearchChange(query: String) {
        _uiState.value = _uiState.value.copy(searchQuery = query)
    }

    fun search() {
        loadProducts(page = 1)
    }

    fun loadMore() {
        val state = _uiState.value
        if (!state.isLoadingMore && state.hasMore) {
            loadProducts(page = state.currentPage + 1)
        }
    }

    fun refresh() {
        loadProducts(page = 1)
    }
}
