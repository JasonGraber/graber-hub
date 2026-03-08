package com.graber.hub.watch.network

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

private val Context.dataStore by preferencesDataStore(name = "graber_hub_watch_settings")

object SettingsStore {
    private val SERVER_URL_KEY = stringPreferencesKey("server_url")
    private const val DEFAULT_URL = "http://192.168.86.43"

    fun serverUrlFlow(context: Context): Flow<String> =
        context.dataStore.data.map { prefs ->
            prefs[SERVER_URL_KEY] ?: DEFAULT_URL
        }

    suspend fun setServerUrl(context: Context, url: String) {
        context.dataStore.edit { prefs ->
            prefs[SERVER_URL_KEY] = url
        }
    }
}
