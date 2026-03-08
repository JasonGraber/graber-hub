package com.graber.hub.watch.network

import com.graber.hub.watch.model.DisplayModeResponse
import com.graber.hub.watch.model.DisplayModeRequest
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST

interface HubApi {
    @GET("api.php/mode")
    suspend fun getDisplayMode(): DisplayModeResponse

    @POST("api.php/mode")
    suspend fun setDisplayMode(@Body body: DisplayModeRequest): DisplayModeResponse
}
