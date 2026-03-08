package com.graber.hub.watch.ui

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.wear.compose.foundation.lazy.ScalingLazyColumn
import androidx.wear.compose.foundation.lazy.rememberScalingLazyListState
import androidx.wear.compose.material.*
import com.graber.hub.watch.model.DisplayMode
import com.graber.hub.watch.model.DisplayModeRequest
import com.graber.hub.watch.network.RetrofitClient
import com.graber.hub.watch.network.SettingsStore
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch

@Composable
fun SettingsScreen(onNavigateToServerUrl: () -> Unit) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val listState = rememberScalingLazyListState()

    var currentMode by remember { mutableStateOf<DisplayMode?>(null) }
    var isLoading by remember { mutableStateOf(true) }
    var error by remember { mutableStateOf<String?>(null) }
    var showSuccess by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        try {
            val url = SettingsStore.serverUrlFlow(context).first()
            val api = RetrofitClient.getApi(url)
            val response = api.getDisplayMode()
            currentMode = DisplayMode.fromKey(response.mode)
        } catch (e: Exception) {
            error = "Connection failed"
        }
        isLoading = false
    }

    LaunchedEffect(showSuccess) {
        if (showSuccess) {
            kotlinx.coroutines.delay(1500)
            showSuccess = false
        }
    }

    ScalingLazyColumn(
        state = listState,
        modifier = Modifier
            .fillMaxSize()
            .background(Color.Black),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(4.dp),
    ) {
        // Title
        item {
            Text(
                text = "⚙️ Settings",
                color = Color.White,
                fontSize = 16.sp,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center,
                modifier = Modifier.padding(bottom = 4.dp),
            )
        }

        // Display Mode header
        item {
            Text(
                text = "Display Mode",
                color = Color(0xFFB0B0C8),
                fontSize = 12.sp,
                fontWeight = FontWeight.Medium,
                textAlign = TextAlign.Center,
            )
        }

        if (isLoading) {
            item {
                CircularProgressIndicator(
                    modifier = Modifier.size(24.dp),
                    indicatorColor = Color(0xFF7C6BF0),
                    strokeWidth = 2.dp,
                )
            }
        } else if (error != null) {
            item {
                Text(
                    text = "⚠️ $error",
                    color = Color(0xFFFF6B6B),
                    fontSize = 12.sp,
                    textAlign = TextAlign.Center,
                )
            }
        } else {
            // Mode options
            DisplayMode.entries.forEach { mode ->
                item {
                    val isSelected = mode == currentMode
                    Chip(
                        onClick = {
                            if (!isSelected) {
                                scope.launch {
                                    try {
                                        val url = SettingsStore.serverUrlFlow(context).first()
                                        val api = RetrofitClient.getApi(url)
                                        api.setDisplayMode(DisplayModeRequest(mode.key))
                                        currentMode = mode
                                        showSuccess = true
                                    } catch (e: Exception) {
                                        error = "Update failed"
                                    }
                                }
                            }
                        },
                        label = {
                            Text(
                                text = "${mode.emoji} ${mode.label}",
                                fontSize = 14.sp,
                                color = if (isSelected) Color.White else Color(0xFFB0B0C8),
                                fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal,
                            )
                        },
                        modifier = Modifier.fillMaxWidth(),
                        colors = ChipDefaults.chipColors(
                            backgroundColor = if (isSelected) Color(0xFF7C6BF0).copy(alpha = 0.3f) else Color(0xFF1A1A2E),
                        ),
                        border = ChipDefaults.chipBorder(),
                    )
                }
            }
        }

        // Success indicator
        if (showSuccess) {
            item {
                Text(
                    text = "✓ Updated",
                    color = Color(0xFF4ADE80),
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Medium,
                    textAlign = TextAlign.Center,
                )
            }
        }

        // Server URL button
        item {
            Spacer(Modifier.height(8.dp))
            Chip(
                onClick = onNavigateToServerUrl,
                label = {
                    Text(
                        text = "🌐 Server URL",
                        fontSize = 13.sp,
                        color = Color(0xFFB0B0C8),
                    )
                },
                modifier = Modifier.fillMaxWidth(),
                colors = ChipDefaults.chipColors(backgroundColor = Color(0xFF1A1A2E)),
            )
        }

        // Version
        item {
            Text(
                text = "v1.1.0",
                color = Color(0xFF6B6B8D),
                fontSize = 10.sp,
                textAlign = TextAlign.Center,
                modifier = Modifier.padding(top = 4.dp),
            )
        }
    }
}
