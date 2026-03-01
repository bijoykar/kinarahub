package com.kinarahub.data.remote

import com.kinarahub.data.remote.models.AuthData
import okhttp3.*
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.*
import org.junit.Before
import org.junit.Test

class TokenRefreshAuthenticatorTest {

    private lateinit var mockServer: MockWebServer
    private lateinit var tokenStore: FakeTokenStore
    private lateinit var authenticator: TokenRefreshAuthenticator

    @Before
    fun setup() {
        mockServer = MockWebServer()
        mockServer.start()
        tokenStore = FakeTokenStore()
        authenticator = TokenRefreshAuthenticator(tokenStore)
    }

    @After
    fun teardown() {
        mockServer.shutdown()
    }

    private fun build401Response(request: Request): Response {
        return Response.Builder()
            .request(request)
            .protocol(Protocol.HTTP_1_1)
            .code(401)
            .message("Unauthorized")
            .body("".toResponseBody("application/json".toMediaType()))
            .build()
    }

    private fun buildRequest(url: String, token: String? = null): Request {
        val builder = Request.Builder().url(url).get()
        if (token != null) {
            builder.header("Authorization", "Bearer $token")
        }
        return builder.build()
    }

    // --- Test: Do not retry if X-Retry-After-Refresh is already set ---

    @Test
    fun `returns null when retry header already present`() {
        val request = Request.Builder()
            .url(mockServer.url("/api/v1/products"))
            .header("X-Retry-After-Refresh", "true")
            .build()
        val response = build401Response(request)

        val result = authenticator.authenticate(null, response)
        assertNull("Should not retry if already retried", result)
    }

    // --- Test: Clear tokens when refresh endpoint itself returns 401 ---

    @Test
    fun `clears tokens when refresh endpoint returns 401`() {
        tokenStore.accessToken = "old-access"
        tokenStore.refreshToken = "old-refresh"

        val request = buildRequest(mockServer.url("/api/v1/auth/refresh").toString())
        val response = build401Response(request)

        val result = authenticator.authenticate(null, response)
        assertNull("Should not retry for refresh endpoint", result)
        assertNull("Should clear access token", tokenStore.accessToken)
        assertNull("Should clear refresh token", tokenStore.refreshToken)
    }

    // --- Test: Returns null and clears tokens when no refresh token stored ---

    @Test
    fun `clears tokens when no refresh token available`() {
        tokenStore.accessToken = "some-access"
        tokenStore.refreshToken = null

        val request = buildRequest(
            mockServer.url("/api/v1/products").toString(),
            "some-access"
        )
        val response = build401Response(request)

        val result = authenticator.authenticate(null, response)
        assertNull("Should not retry without refresh token", result)
        assertNull("Should clear tokens", tokenStore.accessToken)
    }

    // --- Test: Successful refresh saves new tokens and retries request ---

    @Test
    fun `successful refresh saves tokens and retries with new access token`() {
        tokenStore.accessToken = "expired-access"
        tokenStore.refreshToken = "valid-refresh"

        // Queue the refresh response on the mock server
        val refreshResponseBody = """
            {
                "success": true,
                "data": {
                    "access_token": "new-access-token",
                    "refresh_token": "new-refresh-token",
                    "expires_in": 900,
                    "user": {
                        "id": 1,
                        "name": "Test User",
                        "email": "test@example.com",
                        "store_id": 1,
                        "store_name": "Test Store",
                        "role_id": 1
                    }
                },
                "meta": null,
                "error": null
            }
        """.trimIndent()
        mockServer.enqueue(MockResponse().setResponseCode(200).setBody(refreshResponseBody))

        val originalRequest = buildRequest(
            mockServer.url("/api/v1/products").toString(),
            "expired-access"
        )
        val response = build401Response(originalRequest)

        val retryRequest = authenticator.authenticate(null, response)

        assertNotNull("Should return retry request after successful refresh", retryRequest)
        assertEquals(
            "Bearer new-access-token",
            retryRequest!!.header("Authorization")
        )
        assertEquals(
            "true",
            retryRequest.header("X-Retry-After-Refresh")
        )
        assertEquals("new-access-token", tokenStore.accessToken)
        assertEquals("new-refresh-token", tokenStore.refreshToken)
    }

    // --- Test: Failed refresh clears tokens and returns null ---

    @Test
    fun `failed refresh clears tokens and returns null`() {
        tokenStore.accessToken = "expired-access"
        tokenStore.refreshToken = "invalid-refresh"

        val refreshResponseBody = """
            {
                "success": false,
                "data": null,
                "meta": null,
                "error": "Invalid refresh token"
            }
        """.trimIndent()
        mockServer.enqueue(MockResponse().setResponseCode(401).setBody(refreshResponseBody))

        val originalRequest = buildRequest(
            mockServer.url("/api/v1/products").toString(),
            "expired-access"
        )
        val response = build401Response(originalRequest)

        val retryRequest = authenticator.authenticate(null, response)

        assertNull("Should not retry after failed refresh", retryRequest)
        assertNull("Should clear access token", tokenStore.accessToken)
        assertNull("Should clear refresh token", tokenStore.refreshToken)
    }

    // --- Test: Another thread already refreshed — use updated token ---

    @Test
    fun `uses already-refreshed token when another thread updated it`() {
        // Simulate: request used "old-token" but tokenStore was already updated to "new-token"
        tokenStore.accessToken = "new-token-from-other-thread"
        tokenStore.refreshToken = "some-refresh"

        val request = buildRequest(
            mockServer.url("/api/v1/products").toString(),
            "old-token"
        )
        val response = build401Response(request)

        val retryRequest = authenticator.authenticate(null, response)

        assertNotNull("Should retry with the updated token", retryRequest)
        assertEquals(
            "Bearer new-token-from-other-thread",
            retryRequest!!.header("Authorization")
        )
    }

    // --- Test: Refresh request URL is correctly constructed ---

    @Test
    fun `refresh request targets auth refresh endpoint`() {
        tokenStore.accessToken = "expired"
        tokenStore.refreshToken = "valid-refresh"

        val refreshResponseBody = """
            {
                "success": true,
                "data": {
                    "access_token": "new-at",
                    "refresh_token": "new-rt",
                    "expires_in": 900,
                    "user": null
                },
                "meta": null,
                "error": null
            }
        """.trimIndent()
        mockServer.enqueue(MockResponse().setResponseCode(200).setBody(refreshResponseBody))

        val originalRequest = buildRequest(
            mockServer.url("/api/v1/products").toString(),
            "expired"
        )
        val response = build401Response(originalRequest)

        authenticator.authenticate(null, response)

        val recordedRequest = mockServer.takeRequest()
        assertTrue(
            "Refresh should hit /api/v1/auth/refresh",
            recordedRequest.path!!.contains("/api/v1/auth/refresh")
        )
        assertEquals("POST", recordedRequest.method)
    }
}

/**
 * Fake TokenStore for unit testing without EncryptedSharedPreferences.
 * Mirrors the interface of the real TokenStore.
 */
class FakeTokenStore : com.kinarahub.data.local.TokenStore {

    override var accessToken: String? = null
    override var refreshToken: String? = null
    override var userId: Int = -1
    override var userName: String? = null
    override var userEmail: String? = null
    override var storeId: Int = -1
    override var storeName: String? = null
    override var roleId: Int = -1
    override val isLoggedIn: Boolean get() = accessToken != null

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
        accessToken = null
        refreshToken = null
        userId = -1
        userName = null
        userEmail = null
        storeId = -1
        storeName = null
        roleId = -1
    }
}
