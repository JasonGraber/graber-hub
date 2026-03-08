package com.graber.hub.watch

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.runtime.*
import com.graber.hub.watch.ui.SettingsScreen
import com.graber.hub.watch.ui.ServerUrlScreen

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            WatchApp()
        }
    }
}

@Composable
private fun WatchApp() {
    var showServerUrl by remember { mutableStateOf(false) }

    if (showServerUrl) {
        ServerUrlScreen(onBack = { showServerUrl = false })
    } else {
        SettingsScreen(onNavigateToServerUrl = { showServerUrl = true })
    }
}
