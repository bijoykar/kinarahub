package com.kinarahub.ui.screens.sales

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.kinarahub.data.remote.ApiService
import com.kinarahub.data.remote.models.Meta
import com.kinarahub.data.remote.models.Sale
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class SalesHistoryUiState(
    val sales: List<Sale> = emptyList(),
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val error: String? = null,
    val currentPage: Int = 1,
    val meta: Meta? = null,
    val hasMore: Boolean = true
)

@HiltViewModel
class SalesHistoryViewModel @Inject constructor(
    private val apiService: ApiService
) : ViewModel() {

    private val _uiState = MutableStateFlow(SalesHistoryUiState())
    val uiState: StateFlow<SalesHistoryUiState> = _uiState.asStateFlow()

    init {
        loadSales()
    }

    fun loadSales(page: Int = 1) {
        viewModelScope.launch {
            val isFirstPage = page == 1
            _uiState.value = _uiState.value.copy(
                isLoading = isFirstPage,
                isLoadingMore = !isFirstPage,
                error = null
            )

            try {
                val response = apiService.getSales(page = page)
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true && body.data != null) {
                        val currentSales = if (isFirstPage) emptyList()
                        else _uiState.value.sales
                        val meta = body.meta
                        val hasMore = meta != null && (meta.page * meta.perPage) < meta.total

                        _uiState.value = _uiState.value.copy(
                            sales = currentSales + body.data,
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
                            error = body?.error ?: "Failed to load sales"
                        )
                    }
                } else {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        isLoadingMore = false,
                        error = "Failed to load sales (${response.code()})"
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

    fun loadMore() {
        val state = _uiState.value
        if (!state.isLoadingMore && state.hasMore) {
            loadSales(page = state.currentPage + 1)
        }
    }

    fun refresh() {
        loadSales(page = 1)
    }
}
