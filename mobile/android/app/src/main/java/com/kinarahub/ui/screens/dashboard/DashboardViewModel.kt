package com.kinarahub.ui.screens.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.kinarahub.data.local.TokenStore
import com.kinarahub.data.remote.ApiService
import com.kinarahub.data.remote.models.DashboardSummary
import com.kinarahub.data.remote.models.SalesTrend
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

sealed class DashboardUiState {
    data object Loading : DashboardUiState()
    data class Success(val summary: DashboardSummary) : DashboardUiState()
    data class Error(val message: String) : DashboardUiState()
}

@HiltViewModel
class DashboardViewModel @Inject constructor(
    private val apiService: ApiService,
    private val tokenStore: TokenStore
) : ViewModel() {

    private val _uiState = MutableStateFlow<DashboardUiState>(DashboardUiState.Loading)
    val uiState: StateFlow<DashboardUiState> = _uiState.asStateFlow()

    private val _selectedPeriod = MutableStateFlow("week")
    val selectedPeriod: StateFlow<String> = _selectedPeriod.asStateFlow()

    private val _salesTrend = MutableStateFlow<SalesTrend?>(null)
    val salesTrend: StateFlow<SalesTrend?> = _salesTrend.asStateFlow()

    private val _isTrendLoading = MutableStateFlow(false)
    val isTrendLoading: StateFlow<Boolean> = _isTrendLoading.asStateFlow()

    val storeName: String get() = tokenStore.storeName ?: "Kinara Hub"
    val userName: String get() = tokenStore.userName ?: "User"

    init {
        loadDashboard()
    }

    fun loadDashboard() {
        viewModelScope.launch {
            _uiState.value = DashboardUiState.Loading
            try {
                val response = apiService.getDashboardSummary()
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true && body.data != null) {
                        _uiState.value = DashboardUiState.Success(body.data)
                        // Use embedded sales_trend from summary as initial data
                        _salesTrend.value = body.data.salesTrend
                    } else {
                        _uiState.value = DashboardUiState.Error(
                            body?.error ?: "Failed to load dashboard"
                        )
                    }
                } else {
                    _uiState.value = DashboardUiState.Error("Failed to load dashboard (${response.code()})")
                }
            } catch (e: Exception) {
                _uiState.value = DashboardUiState.Error(
                    "Network error. Please check your connection."
                )
            }
        }
    }

    fun loadSalesTrend(period: String) {
        _selectedPeriod.value = period
        viewModelScope.launch {
            _isTrendLoading.value = true
            try {
                // The dashboard summary endpoint returns sales_trend data.
                // When the API supports a period query param, this will filter by period.
                // For now, we re-fetch the full summary to get trend data.
                val response = apiService.getDashboardSummary()
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true && body.data != null) {
                        _salesTrend.value = body.data.salesTrend
                    }
                }
            } catch (_: Exception) {
                // Keep existing trend data on failure
            } finally {
                _isTrendLoading.value = false
            }
        }
    }

    fun logout() {
        viewModelScope.launch {
            try {
                apiService.logout()
            } catch (_: Exception) {
                // Best-effort logout
            }
            tokenStore.clear()
        }
    }
}
