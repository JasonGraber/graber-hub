package com.graberhub.companion.network

import com.graberhub.companion.data.models.*
import retrofit2.Response
import retrofit2.http.*

interface ApiService {

    @GET("api.php/dashboard")
    suspend fun getDashboard(): Response<DashboardResponse>

    // Chores
    @POST("api.php/chores")
    suspend fun createChore(@Body request: CreateChoreRequest): Response<ApiResponse>

    @PUT("api.php/chores/{id}/toggle")
    suspend fun toggleChore(@Path("id") id: Int, @Query("date") date: String): Response<ApiResponse>

    @PUT("api.php/chores/{id}")
    suspend fun updateChore(@Path("id") id: Int, @Body request: UpdateChoreRequest): Response<ApiResponse>

    @DELETE("api.php/chores/{id}")
    suspend fun deleteChore(@Path("id") id: Int, @Query("date") date: String): Response<ApiResponse>

    // Schedule
    @POST("api.php/schedule")
    suspend fun createScheduleBlock(@Body request: CreateScheduleRequest): Response<ApiResponse>

    @PUT("api.php/schedule/{id}")
    suspend fun updateScheduleBlock(@Path("id") id: Int, @Body request: UpdateScheduleRequest): Response<ApiResponse>

    @POST("api.php/schedule/template")
    suspend fun applyTemplate(@Body request: TemplateRequest): Response<ApiResponse>

    @DELETE("api.php/schedule/{id}")
    suspend fun deleteScheduleBlock(@Path("id") id: Int, @Query("date") date: String): Response<ApiResponse>

    // Countdowns
    @GET("api.php/countdowns")
    suspend fun getCountdowns(): Response<List<Countdown>>

    @POST("api.php/countdowns")
    suspend fun createCountdown(@Body request: CreateCountdownRequest): Response<ApiResponse>

    @DELETE("api.php/countdowns/{id}")
    suspend fun deleteCountdown(@Path("id") id: Int): Response<ApiResponse>

    // Verses
    @GET("api.php/verses")
    suspend fun getVerses(): Response<List<Verse>>

    @POST("api.php/verses")
    suspend fun createVerse(@Body request: CreateVerseRequest): Response<ApiResponse>

    // Timer
    @GET("api.php/timer")
    suspend fun getTimer(): Response<TimerStatus>

    @POST("api.php/timer")
    suspend fun createTimer(@Body request: CreateTimerRequest): Response<ApiResponse>

    @DELETE("api.php/timer")
    suspend fun deleteTimer(): Response<ApiResponse>

    // Kids
    @PUT("api.php/kids/{id}")
    suspend fun updateKid(@Path("id") id: Int, @Body request: UpdateKidColorRequest): Response<ApiResponse>

    // Photo Settings
    @GET("api.php/photo-settings")
    suspend fun getPhotoSettings(): Response<PhotoSettings>

    @POST("api.php/photo-settings")
    suspend fun updatePhotoSettings(@Body request: PhotoSettingsRequest): Response<PhotoSettings>
}
