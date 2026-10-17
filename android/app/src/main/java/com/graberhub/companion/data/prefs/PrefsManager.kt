package com.graberhub.companion.data.prefs

import android.content.Context
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import com.graberhub.companion.data.models.KidConfig

class PrefsManager(context: Context) {

    private val prefs = context.getSharedPreferences("graber_hub_prefs", Context.MODE_PRIVATE)
    private val gson = Gson()

    companion object {
        private const val KEY_SERVER_URL = "server_url"
        private const val KEY_KIDS = "kids_config"

        const val HOME_URL = "http://192.168.1.100/"
        const val TAILSCALE_URL = "http://100.64.0.1/"
        const val DEFAULT_SERVER = HOME_URL

        val DEFAULT_KIDS = listOf(
            KidConfig(1, "Kid 1", "#ec5281"),
            KidConfig(2, "Kid 2", "#03a9f4"),
            KidConfig(3, "Kid 3", "#a78bfa"),
            KidConfig(4, "Kid 4", "#4ade80")
        )
    }

    var serverUrl: String
        get() {
            val url = prefs.getString(KEY_SERVER_URL, DEFAULT_SERVER) ?: DEFAULT_SERVER
            // Always ensure trailing slash for Retrofit
            return if (url.endsWith("/")) url else "$url/"
        }
        set(value) {
            // Store with trailing slash to avoid issues
            val url = if (value.endsWith("/")) value else "$value/"
            prefs.edit().putString(KEY_SERVER_URL, url).apply()
        }

    val isHomeNetwork: Boolean
        get() = serverUrl == HOME_URL

    val isTailscaleNetwork: Boolean
        get() = serverUrl == TAILSCALE_URL

    val alternateUrl: String
        get() = if (isHomeNetwork) TAILSCALE_URL else HOME_URL

    var kidsConfig: List<KidConfig>
        get() {
            val json = prefs.getString(KEY_KIDS, null) ?: return DEFAULT_KIDS
            return try {
                val type = object : TypeToken<List<KidConfig>>() {}.type
                gson.fromJson<List<KidConfig>>(json, type) ?: DEFAULT_KIDS
            } catch (e: Exception) {
                DEFAULT_KIDS
            }
        }
        set(value) {
            prefs.edit().putString(KEY_KIDS, gson.toJson(value)).apply()
        }

    fun resetKids() {
        prefs.edit().remove(KEY_KIDS).apply()
    }
}
