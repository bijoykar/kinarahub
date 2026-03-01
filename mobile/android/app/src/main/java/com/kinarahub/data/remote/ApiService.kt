package com.kinarahub.data.remote

import com.kinarahub.data.remote.models.*
import retrofit2.Response
import retrofit2.http.*

interface ApiService {

    // --- Auth ---

    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): Response<ApiResponse<AuthData>>

    @POST("auth/refresh")
    suspend fun refreshToken(@Body request: RefreshRequest): Response<ApiResponse<AuthData>>

    @POST("auth/logout")
    suspend fun logout(): Response<ApiResponse<Unit>>

    // --- Products ---

    @GET("products")
    suspend fun getProducts(
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 20,
        @Query("search") search: String? = null,
        @Query("category_id") categoryId: Int? = null,
        @Query("status") status: String? = null
    ): Response<ApiResponse<List<Product>>>

    @GET("products/{id}")
    suspend fun getProduct(@Path("id") id: Int): Response<ApiResponse<Product>>

    @POST("products")
    suspend fun createProduct(@Body request: CreateProductRequest): Response<ApiResponse<Product>>

    @PUT("products/{id}")
    suspend fun updateProduct(
        @Path("id") id: Int,
        @Body request: CreateProductRequest
    ): Response<ApiResponse<Product>>

    @DELETE("products/{id}")
    suspend fun deleteProduct(@Path("id") id: Int): Response<ApiResponse<Unit>>

    @GET("products/{id}/variants")
    suspend fun getProductVariants(@Path("id") productId: Int): Response<ApiResponse<List<ProductVariant>>>

    // --- Sales ---

    @POST("sales")
    suspend fun createSale(@Body request: CreateSaleRequest): Response<ApiResponse<Sale>>

    @GET("sales")
    suspend fun getSales(
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 20,
        @Query("from") from: String? = null,
        @Query("to") to: String? = null
    ): Response<ApiResponse<List<Sale>>>

    @GET("sales/{id}")
    suspend fun getSale(@Path("id") id: Int): Response<ApiResponse<Sale>>

    // --- Customers ---

    @GET("customers")
    suspend fun getCustomers(
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 20,
        @Query("search") search: String? = null
    ): Response<ApiResponse<List<Customer>>>

    @POST("customers")
    suspend fun createCustomer(@Body request: CreateCustomerRequest): Response<ApiResponse<Customer>>

    @GET("customers/{id}/credits")
    suspend fun getCustomerCredits(@Path("id") customerId: Int): Response<ApiResponse<List<CustomerCredit>>>

    @POST("customers/{id}/payments")
    suspend fun recordPayment(
        @Path("id") customerId: Int,
        @Body request: RecordPaymentRequest
    ): Response<ApiResponse<Unit>>

    // --- Dashboard ---

    @GET("dashboard/summary")
    suspend fun getDashboardSummary(): Response<ApiResponse<DashboardSummary>>
}
