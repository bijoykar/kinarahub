package com.kinarahub.data.remote.models

import com.google.gson.annotations.SerializedName

data class Customer(
    val id: Int,
    @SerializedName("store_id")
    val storeId: Int,
    val name: String,
    val mobile: String?,
    val email: String?,
    @SerializedName("is_default")
    val isDefault: Int,
    @SerializedName("outstanding_balance")
    val outstandingBalance: Double,
    @SerializedName("created_at")
    val createdAt: String?
)

data class CreateCustomerRequest(
    val name: String,
    val mobile: String?,
    val email: String?
)

data class CustomerCredit(
    val id: Int,
    @SerializedName("customer_id")
    val customerId: Int,
    @SerializedName("sale_id")
    val saleId: Int?,
    @SerializedName("sale_number")
    val saleNumber: String?,
    @SerializedName("amount_due")
    val amountDue: Double,
    @SerializedName("amount_paid")
    val amountPaid: Double,
    val balance: Double,
    @SerializedName("due_date")
    val dueDate: String?,
    @SerializedName("created_at")
    val createdAt: String?
)

data class RecordPaymentRequest(
    val amount: Double,
    @SerializedName("payment_method")
    val paymentMethod: String,
    val notes: String? = null
)
