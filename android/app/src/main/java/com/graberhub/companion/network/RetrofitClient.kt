package com.graberhub.companion.network

import android.util.Log
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object RetrofitClient {

    private const val TAG = "RetrofitClient"
    private var baseUrl = "http://192.168.86.43/"
    private var retrofit: Retrofit? = null
    private var apiService: ApiService? = null

    fun init(serverUrl: String) {
        val url = if (serverUrl.endsWith("/")) serverUrl else "$serverUrl/"
        Log.d(TAG, "init() called with serverUrl=$serverUrl → baseUrl=$url")
        if (url != baseUrl || retrofit == null) {
            baseUrl = url
            retrofit = null
            apiService = null
            Log.d(TAG, "Retrofit instance reset, will rebuild on next request")
        }
    }

    private fun getRetrofit(): Retrofit {
        if (retrofit == null) {
            Log.d(TAG, "Building new Retrofit instance with baseUrl=$baseUrl")
            val logging = HttpLoggingInterceptor { message ->
                Log.d(TAG, message)
            }.apply {
                level = HttpLoggingInterceptor.Level.BODY
            }
            val client = OkHttpClient.Builder()
                .addInterceptor(logging)
                .connectTimeout(15, TimeUnit.SECONDS)
                .readTimeout(30, TimeUnit.SECONDS)
                .writeTimeout(15, TimeUnit.SECONDS)
                .build()
            retrofit = Retrofit.Builder()
                .baseUrl(baseUrl)
                .client(client)
                .addConverterFactory(GsonConverterFactory.create())
                .build()
        }
        return retrofit!!
    }

    fun getApiService(): ApiService {
        if (apiService == null) {
            apiService = getRetrofit().create(ApiService::class.java)
        }
        return apiService!!
    }

    suspend fun testConnection(): Boolean {
        return try {
            Log.d(TAG, "testConnection() → baseUrl=$baseUrl")
            val response = getApiService().getDashboard()
            Log.d(TAG, "testConnection() → code=${response.code()}, success=${response.isSuccessful}")
            response.isSuccessful
        } catch (e: Exception) {
            Log.e(TAG, "testConnection() failed: ${e.javaClass.simpleName}: ${e.message}", e)
            false
        }
    }
}
