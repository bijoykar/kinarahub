package com.kinarahub.data.remote

import com.kinarahub.data.local.TokenStore
import com.kinarahub.data.remote.models.ApiResponse
import com.kinarahub.data.remote.models.AuthData
import com.kinarahub.data.remote.models.RefreshRequest
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import okhttp3.*
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.toRequestBody
import javax.inject.Inject

class TokenRefreshAuthenticator @Inject constructor(
    private val tokenStore: TokenStore
) : Authenticator {

    private val gson = Gson()

    @Volatile
    private var isRefreshing = false

    override fun authenticate(route: Route?, response: Response): Request? {
        // Don't retry if we already attempted refresh
        if (response.request.header("X-Retry-After-Refresh") != null) {
            return null
        }

        // Don't try to refresh if we're on the refresh endpoint itself
        if (response.request.url.encodedPath.contains("auth/refresh")) {
            tokenStore.clear()
            return null
        }

        synchronized(this) {
            val currentToken = tokenStore.accessToken
            val requestToken = response.request.header("Authorization")
                ?.removePrefix("Bearer ")

            // If another thread already refreshed the token, retry with the new one
            if (currentToken != null && currentToken != requestToken) {
                return response.request.newBuilder()
                    .header("Authorization", "Bearer $currentToken")
                    .header("X-Retry-After-Refresh", "true")
                    .build()
            }

            if (isRefreshing) return null
            isRefreshing = true
        }

        try {
            val refreshToken = tokenStore.refreshToken ?: run {
                tokenStore.clear()
                return null
            }

            val refreshBody = gson.toJson(RefreshRequest(refreshToken))
                .toRequestBody("application/json".toMediaType())

            val refreshRequest = Request.Builder()
                .url(response.request.url.toString().substringBefore("/api/v1/") + "/api/v1/auth/refresh")
                .post(refreshBody)
                .header("Accept", "application/json")
                .header("Content-Type", "application/json")
                .build()

            val client = OkHttpClient.Builder().build()
            val refreshResponse = client.newCall(refreshRequest).execute()

            if (refreshResponse.isSuccessful) {
                val body = refreshResponse.body?.string()
                val type = object : TypeToken<ApiResponse<AuthData>>() {}.type
                val apiResponse: ApiResponse<AuthData>? = gson.fromJson(body, type)

                if (apiResponse?.success == true && apiResponse.data != null) {
                    tokenStore.saveAuth(apiResponse.data)

                    return response.request.newBuilder()
                        .header("Authorization", "Bearer ${apiResponse.data.accessToken}")
                        .header("X-Retry-After-Refresh", "true")
                        .build()
                }
            }

            // Refresh failed -- clear everything
            tokenStore.clear()
            return null
        } finally {
            synchronized(this) {
                isRefreshing = false
            }
        }
    }
}
