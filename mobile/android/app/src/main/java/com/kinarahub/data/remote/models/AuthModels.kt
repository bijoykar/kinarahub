package com.kinarahub.data.remote.models

import com.google.gson.annotations.SerializedName

// --- Requests ---

data class LoginRequest(
    val email: String,
    val password: String
)

data class RefreshRequest(
    @SerializedName("refresh_token")
    val refreshToken: String
)

data class LogoutRequest(
    @SerializedName("refresh_token")
    val refreshToken: String
)

// --- Responses ---

data class AuthData(
    @SerializedName("access_token")
    val accessToken: String,
    @SerializedName("refresh_token")
    val refreshToken: String?,
    @SerializedName("token_type")
    val tokenType: String?,
    @SerializedName("expires_in")
    val expiresIn: Int,
    val user: UserInfo?
)

data class UserInfo(
    val id: Int,
    val name: String,
    val email: String,
    @SerializedName("store_id")
    val storeId: Int,
    @SerializedName("store_name")
    val storeName: String?,
    @SerializedName("role_id")
    val roleId: Int
)

/**
 * Generic message response used by logout, update, delete endpoints.
 */
data class MessageResponse(
    val message: String?
)
