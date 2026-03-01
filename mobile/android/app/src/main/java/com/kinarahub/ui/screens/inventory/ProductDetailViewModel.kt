package com.kinarahub.ui.screens.inventory

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.kinarahub.data.remote.ApiService
import com.kinarahub.data.remote.models.Product
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class ProductDetailUiState {
    data object Loading : ProductDetailUiState()
    data class Success(val product: Product) : ProductDetailUiState()
    data class Error(val message: String) : ProductDetailUiState()
}

@HiltViewModel
class ProductDetailViewModel @Inject constructor(
    private val apiService: ApiService
) : ViewModel() {

    private val _uiState = MutableStateFlow<ProductDetailUiState>(ProductDetailUiState.Loading)
    val uiState: StateFlow<ProductDetailUiState> = _uiState.asStateFlow()

    fun loadProduct(productId: Int) {
        viewModelScope.launch {
            _uiState.value = ProductDetailUiState.Loading
            try {
                val response = apiService.getProduct(productId)
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true && body.data != null) {
                        _uiState.value = ProductDetailUiState.Success(body.data)
                    } else {
                        _uiState.value = ProductDetailUiState.Error(
                            body?.error ?: "Failed to load product"
                        )
                    }
                } else {
                    _uiState.value = ProductDetailUiState.Error(
                        "Failed to load product (${response.code()})"
                    )
                }
            } catch (e: Exception) {
                _uiState.value = ProductDetailUiState.Error(
                    "Network error. Please check your connection."
                )
            }
        }
    }
}
