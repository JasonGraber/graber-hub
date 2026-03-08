package com.graber.hub.watch.model

import com.google.gson.annotations.SerializedName

data class DisplayModeResponse(
    @SerializedName("mode") val mode: String,
)

data class DisplayModeRequest(
    @SerializedName("mode") val mode: String,
)

enum class DisplayMode(val key: String, val label: String, val emoji: String) {
    DASHBOARD("dashboard", "Dashboard", "📋"),
    PHOTOS("photos", "Photos", "🖼️"),
    AUTO("auto", "Auto", "🌙");

    companion object {
        fun fromKey(key: String): DisplayMode =
            entries.find { it.key == key } ?: DASHBOARD
    }
}
