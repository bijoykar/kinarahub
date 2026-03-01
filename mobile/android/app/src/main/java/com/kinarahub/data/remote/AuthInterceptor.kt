package com.kinarahub.data.remote

import com.kinarahub.data.local.TokenStore
import okhttp3.Interceptor
import okhttp3.Response
import javax.inject.Inject

class AuthInterceptor @Inject constructor(
    private val tokenStore: TokenStore
) : Interceptor {

    override fun intercept(chain: Interceptor.Chain): Response {
        val original = chain.request()

        // Skip auth header for login and refresh endpoints
        val path = original.url.encodedPath
        if (path.contains("auth/login") || path.contains("auth/refresh")) {
            return chain.proceed(original)
        }

        val token = tokenStore.accessToken ?: return chain.proceed(original)

        val authenticatedRequest = original.newBuilder()
            .header("Authorization", "Bearer $token")
            .header("Accept", "application/json")
            .header("Content-Type", "application/json")
            .build()

        return chain.proceed(authenticatedRequest)
    }
}
