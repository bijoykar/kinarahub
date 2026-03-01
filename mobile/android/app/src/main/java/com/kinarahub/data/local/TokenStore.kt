package com.kinarahub.data.local

import android.content.Context
import android.content.SharedPreferences
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import com.kinarahub.data.remote.models.AuthData
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

interface TokenStore {
    var accessToken: String?
    var refreshToken: String?
    var userId: Int
    var userName: String?
    var userEmail: String?
    var storeId: Int
    var storeName: String?
    var roleId: Int
    val isLoggedIn: Boolean
    fun saveAuth(authData: AuthData)
    fun clear()
}

@Singleton
class TokenStoreImpl @Inject constructor(
    @ApplicationContext context: Context
) : TokenStore {

    private val masterKey = MasterKey.Builder(context)
        .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
        .build()

    private val prefs: SharedPreferences = EncryptedSharedPreferences.create(
        context,
        "kinarahub_secure_prefs",
        masterKey,
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
    )

    override var accessToken: String?
        get() = prefs.getString(KEY_ACCESS_TOKEN, null)
        set(value) = prefs.edit().putString(KEY_ACCESS_TOKEN, value).apply()

    override var refreshToken: String?
        get() = prefs.getString(KEY_REFRESH_TOKEN, null)
        set(value) = prefs.edit().putString(KEY_REFRESH_TOKEN, value).apply()

    override var userId: Int
        get() = prefs.getInt(KEY_USER_ID, -1)
        set(value) = prefs.edit().putInt(KEY_USER_ID, value).apply()

    override var userName: String?
        get() = prefs.getString(KEY_USER_NAME, null)
        set(value) = prefs.edit().putString(KEY_USER_NAME, value).apply()

    override var userEmail: String?
        get() = prefs.getString(KEY_USER_EMAIL, null)
        set(value) = prefs.edit().putString(KEY_USER_EMAIL, value).apply()

    override var storeId: Int
        get() = prefs.getInt(KEY_STORE_ID, -1)
        set(value) = prefs.edit().putInt(KEY_STORE_ID, value).apply()

    override var storeName: String?
        get() = prefs.getString(KEY_STORE_NAME, null)
        set(value) = prefs.edit().putString(KEY_STORE_NAME, value).apply()

    override var roleId: Int
        get() = prefs.getInt(KEY_ROLE_ID, -1)
        set(value) = prefs.edit().putInt(KEY_ROLE_ID, value).apply()

    override val isLoggedIn: Boolean
        get() = accessToken != null

    override fun saveAuth(authData: AuthData) {
        accessToken = authData.accessToken
        authData.refreshToken?.let { refreshToken = it }
        authData.user?.let { user ->
            userId = user.id
            userName = user.name
            userEmail = user.email
            storeId = user.storeId
            storeName = user.storeName
            roleId = user.roleId
        }
    }

    override fun clear() {
        prefs.edit().clear().apply()
    }

    companion object {
        private const val KEY_ACCESS_TOKEN = "access_token"
        private const val KEY_REFRESH_TOKEN = "refresh_token"
        private const val KEY_USER_ID = "user_id"
        private const val KEY_USER_NAME = "user_name"
        private const val KEY_USER_EMAIL = "user_email"
        private const val KEY_STORE_ID = "store_id"
        private const val KEY_STORE_NAME = "store_name"
        private const val KEY_ROLE_ID = "role_id"
    }
}
