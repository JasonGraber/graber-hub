package com.graberhub.companion.ui.screens

import androidx.compose.animation.*
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.graberhub.companion.data.models.Verse
import com.graberhub.companion.data.prefs.PrefsManager
import com.graberhub.companion.ui.components.ColorPickerDialog
import com.graberhub.companion.ui.theme.*
import com.graberhub.companion.viewmodel.ConnectionState
import com.graberhub.companion.viewmodel.MainViewModel

@Composable
fun SettingsScreen(viewModel: MainViewModel) {
    val connectionStatus by viewModel.connectionStatus.collectAsStateWithLifecycle()
    val connectionState by viewModel.connectionState.collectAsStateWithLifecycle()
    val activeNetwork by viewModel.activeNetwork.collectAsStateWithLifecycle()
    val kidsConfig by viewModel.kidsConfig.collectAsStateWithLifecycle()
    val verses by viewModel.verses.collectAsStateWithLifecycle()
    val photoSettings by viewModel.photoSettings.collectAsStateWithLifecycle()
    val photoSettingsSaved by viewModel.photoSettingsSaved.collectAsStateWithLifecycle()
    var serverUrl by remember { mutableStateOf(viewModel.prefs.serverUrl) }
    var albumUrl by remember { mutableStateOf("") }
    var showVerseDialog by remember { mutableStateOf(false) }
    var showColorPicker by remember { mutableStateOf<Int?>(null) }

    LaunchedEffect(Unit) {
        viewModel.loadVerses()
        viewModel.loadPhotoSettings()
    }

    // Sync album URL from API when photo settings load
    LaunchedEffect(photoSettings) {
        photoSettings?.album_url?.let { albumUrl = it }
    }

    // Keep serverUrl in sync when network switches
    LaunchedEffect(activeNetwork) {
        serverUrl = viewModel.prefs.serverUrl
    }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        item {
            Text(
                "⚙️ Settings",
                style = MaterialTheme.typography.headlineMedium,
                color = TextPrimary,
                fontWeight = FontWeight.Bold
            )
        }

        // Network Quick-Switch
        item {
            SettingsCard(title = "📡 Network") {
                // Connection status indicator
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    // Status dot
                    Box(
                        modifier = Modifier
                            .size(12.dp)
                            .clip(CircleShape)
                            .background(
                                when (connectionState) {
                                    ConnectionState.CONNECTED -> CheckGreen
                                    ConnectionState.CONNECTING -> Color(0xFFFFA726) // Orange
                                    ConnectionState.FAILED -> ShedPink
                                    ConnectionState.UNKNOWN -> TextHint
                                }
                            )
                    )
                    Spacer(Modifier.width(8.dp))
                    Text(
                        when (connectionState) {
                            ConnectionState.CONNECTED -> "Connected"
                            ConnectionState.CONNECTING -> "Connecting..."
                            ConnectionState.FAILED -> "Disconnected"
                            ConnectionState.UNKNOWN -> "Unknown"
                        },
                        style = MaterialTheme.typography.bodyMedium,
                        color = when (connectionState) {
                            ConnectionState.CONNECTED -> CheckGreen
                            ConnectionState.CONNECTING -> Color(0xFFFFA726)
                            ConnectionState.FAILED -> ShedPink
                            ConnectionState.UNKNOWN -> TextSecondary
                        },
                        fontWeight = FontWeight.Medium
                    )
                    Spacer(Modifier.weight(1f))
                    connectionStatus?.let { status ->
                        Text(
                            status,
                            style = MaterialTheme.typography.bodySmall,
                            color = TextSecondary
                        )
                    }
                }

                // Quick-switch buttons
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    val isHome = activeNetwork == "home"
                    val isTailscale = activeNetwork == "tailscale"

                    // Home button
                    Button(
                        onClick = { viewModel.switchNetwork(PrefsManager.HOME_URL) },
                        modifier = Modifier
                            .weight(1f)
                            .height(56.dp)
                            .then(
                                if (isHome) Modifier.border(2.dp, ShedBlueDark, RoundedCornerShape(12.dp))
                                else Modifier
                            ),
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(
                            containerColor = if (isHome) ShedBlue else BackgroundGray,
                            contentColor = if (isHome) Color.White else TextPrimary
                        ),
                        elevation = ButtonDefaults.buttonElevation(
                            defaultElevation = if (isHome) 4.dp else 0.dp
                        )
                    ) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text("🏠", fontSize = 18.sp)
                            Text(
                                "Home",
                                fontSize = 12.sp,
                                fontWeight = if (isHome) FontWeight.Bold else FontWeight.Normal
                            )
                        }
                    }

                    // Tailscale button
                    Button(
                        onClick = { viewModel.switchNetwork(PrefsManager.TAILSCALE_URL) },
                        modifier = Modifier
                            .weight(1f)
                            .height(56.dp)
                            .then(
                                if (isTailscale) Modifier.border(2.dp, ShedBlueDark, RoundedCornerShape(12.dp))
                                else Modifier
                            ),
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(
                            containerColor = if (isTailscale) ShedBlue else BackgroundGray,
                            contentColor = if (isTailscale) Color.White else TextPrimary
                        ),
                        elevation = ButtonDefaults.buttonElevation(
                            defaultElevation = if (isTailscale) 4.dp else 0.dp
                        )
                    ) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text("🌐", fontSize = 18.sp)
                            Text(
                                "Tailscale",
                                fontSize = 12.sp,
                                fontWeight = if (isTailscale) FontWeight.Bold else FontWeight.Normal
                            )
                        }
                    }
                }

                Spacer(Modifier.height(12.dp))

                // Reconnect button
                OutlinedButton(
                    onClick = { viewModel.reconnect() },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = connectionState != ConnectionState.CONNECTING
                ) {
                    Icon(
                        Icons.Default.Refresh,
                        contentDescription = "Reconnect",
                        modifier = Modifier.size(18.dp)
                    )
                    Spacer(Modifier.width(6.dp))
                    Text("🔄 Reconnect")
                }
            }
        }

        // Server URL section (advanced)
        item {
            SettingsCard(title = "🌐 Server Connection") {
                OutlinedTextField(
                    value = serverUrl,
                    onValueChange = { serverUrl = it },
                    label = { Text("Server URL") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true
                )

                Spacer(Modifier.height(12.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End
                ) {
                    OutlinedButton(
                        onClick = {
                            viewModel.updateServerUrl(serverUrl)
                            viewModel.testConnection()
                        }
                    ) {
                        Text("Save & Test")
                    }
                }
            }
        }

        // Kid Colors section
        item {
            SettingsCard(title = "🎨 Kid Colors") {
                kidsConfig.forEach { kidConfig ->
                    val color = try {
                        Color(android.graphics.Color.parseColor(kidConfig.color))
                    } catch (_: Exception) { ShedBlue }

                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 6.dp)
                            .clickable { showColorPicker = kidConfig.id },
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Box(
                            modifier = Modifier
                                .size(36.dp)
                                .clip(CircleShape)
                                .background(color),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                kidConfig.name.first().uppercase(),
                                color = Color.White,
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp
                            )
                        }
                        Spacer(Modifier.width(12.dp))
                        Text(
                            kidConfig.name,
                            style = MaterialTheme.typography.bodyLarge,
                            color = TextPrimary,
                            modifier = Modifier.weight(1f)
                        )
                        Text(
                            kidConfig.color,
                            style = MaterialTheme.typography.bodySmall,
                            color = TextSecondary
                        )
                    }
                }
            }
        }

        // Verses section
        item {
            SettingsCard(title = "📖 Bible Verses") {
                if (verses.isEmpty()) {
                    Text(
                        "No verses yet. Add one to display on the dashboard.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = TextHint,
                        modifier = Modifier.padding(vertical = 8.dp)
                    )
                } else {
                    verses.forEach { verse ->
                        Surface(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(vertical = 4.dp),
                            shape = RoundedCornerShape(8.dp),
                            color = BackgroundGray
                        ) {
                            Row(
                                modifier = Modifier.padding(12.dp),
                                verticalAlignment = Alignment.Top
                            ) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(
                                        "\"${verse.text}\"",
                                        style = MaterialTheme.typography.bodyMedium,
                                        color = TextPrimary
                                    )
                                    Spacer(Modifier.height(4.dp))
                                    Text(
                                        "— ${verse.reference}",
                                        style = MaterialTheme.typography.labelMedium,
                                        color = ShedBlue,
                                        fontWeight = FontWeight.Medium
                                    )
                                }
                            }
                        }
                    }
                }

                Spacer(Modifier.height(8.dp))

                OutlinedButton(
                    onClick = { showVerseDialog = true },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(18.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("Add Verse")
                }
            }
        }

        // Display Mode section
        item {
            val currentMode = photoSettings?.mode ?: "dashboard"
            SettingsCard(title = "📺 Display Mode") {
                val modes = listOf(
                    Triple("dashboard", "Dashboard", "Chores, schedule, and countdowns"),
                    Triple("photos", "Photos", "Full-screen photo slideshow"),
                    Triple("auto", "Auto", "Photos at night (8 PM–7 AM), dashboard during day")
                )
                modes.forEach { (value, label, description) ->
                    val isSelected = currentMode == value
                    Surface(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 4.dp)
                            .clip(RoundedCornerShape(10.dp))
                            .clickable { viewModel.updateDisplayMode(value) }
                            .then(
                                if (isSelected) Modifier.border(
                                    2.dp, ShedBlue, RoundedCornerShape(10.dp)
                                ) else Modifier.border(
                                    1.dp, BorderLight, RoundedCornerShape(10.dp)
                                )
                            ),
                        shape = RoundedCornerShape(10.dp),
                        color = if (isSelected) ShedBlueLight.copy(alpha = 0.3f) else BackgroundGray
                    ) {
                        Row(
                            modifier = Modifier.padding(14.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    label,
                                    style = MaterialTheme.typography.bodyLarge,
                                    color = if (isSelected) ShedBlueDark else TextPrimary,
                                    fontWeight = if (isSelected) FontWeight.SemiBold else FontWeight.Normal
                                )
                                Text(
                                    description,
                                    style = MaterialTheme.typography.bodySmall,
                                    color = TextSecondary
                                )
                            }
                            if (isSelected) {
                                Icon(
                                    Icons.Default.Check,
                                    contentDescription = "Selected",
                                    tint = ShedBlue,
                                    modifier = Modifier.size(22.dp)
                                )
                            }
                        }
                    }
                }
                // Success feedback
                AnimatedVisibility(
                    visible = photoSettingsSaved == "mode",
                    enter = fadeIn(),
                    exit = fadeOut()
                ) {
                    Text(
                        "✓ Display mode saved",
                        color = CheckGreen,
                        style = MaterialTheme.typography.bodyMedium,
                        modifier = Modifier.padding(top = 8.dp)
                    )
                }
            }
        }

        // Photo Album section
        item {
            SettingsCard(title = "🖼️ Photo Album") {
                OutlinedTextField(
                    value = albumUrl,
                    onValueChange = { albumUrl = it },
                    label = { Text("Google Photos Album URL") },
                    placeholder = { Text("Paste shared album link") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true
                )

                Spacer(Modifier.height(12.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    AnimatedVisibility(
                        visible = photoSettingsSaved == "album",
                        enter = fadeIn(),
                        exit = fadeOut()
                    ) {
                        Text(
                            "✓ Album URL saved",
                            color = CheckGreen,
                            style = MaterialTheme.typography.bodyMedium
                        )
                    }

                    Spacer(Modifier.weight(1f))

                    Button(
                        onClick = { viewModel.updateAlbumUrl(albumUrl) },
                        colors = ButtonDefaults.buttonColors(containerColor = ShedBlue),
                        enabled = albumUrl.isNotBlank()
                    ) {
                        Text("Save")
                    }
                }
            }
        }

        // Version
        item {
            SettingsCard(title = "ℹ️ About") {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text("Version", color = TextSecondary)
                    Text("1.1.2", color = TextPrimary, fontWeight = FontWeight.Medium)
                }
            }
        }

        item { Spacer(Modifier.height(80.dp)) }
    }

    // Color picker dialog
    showColorPicker?.let { kidId ->
        val currentColor = kidsConfig.find { it.id == kidId }?.color ?: "#03a9f4"
        ColorPickerDialog(
            selectedColor = currentColor,
            onColorSelected = { color ->
                viewModel.updateKidColor(kidId, color)
                showColorPicker = null
            },
            onDismiss = { showColorPicker = null }
        )
    }

    // Add verse dialog
    if (showVerseDialog) {
        AddVerseDialog(
            onSave = { text, reference ->
                viewModel.addVerse(text, reference)
                showVerseDialog = false
            },
            onDismiss = { showVerseDialog = false }
        )
    }
}

@Composable
private fun SettingsCard(
    title: String,
    content: @Composable ColumnScope.() -> Unit
) {
    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .shadow(2.dp, RoundedCornerShape(12.dp)),
        shape = RoundedCornerShape(12.dp),
        color = CardBackground
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                title,
                style = MaterialTheme.typography.titleLarge,
                color = TextPrimary,
                fontWeight = FontWeight.SemiBold,
                modifier = Modifier.padding(bottom = 12.dp)
            )
            content()
        }
    }
}

@Composable
private fun AddVerseDialog(
    onSave: (text: String, reference: String) -> Unit,
    onDismiss: () -> Unit
) {
    var text by remember { mutableStateOf("") }
    var reference by remember { mutableStateOf("") }

    Dialog(onDismissRequest = onDismiss) {
        Surface(
            shape = RoundedCornerShape(16.dp),
            color = CardBackground
        ) {
            Column(modifier = Modifier.padding(20.dp)) {
                Text(
                    "Add Bible Verse",
                    style = MaterialTheme.typography.headlineSmall,
                    color = TextPrimary
                )

                Spacer(Modifier.height(16.dp))

                OutlinedTextField(
                    value = text,
                    onValueChange = { text = it },
                    label = { Text("Verse Text") },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 3,
                    maxLines = 5
                )

                Spacer(Modifier.height(12.dp))

                OutlinedTextField(
                    value = reference,
                    onValueChange = { reference = it },
                    label = { Text("Reference (e.g. John 3:16)") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true
                )

                Spacer(Modifier.height(16.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End
                ) {
                    TextButton(onClick = onDismiss) { Text("Cancel") }
                    Spacer(Modifier.width(8.dp))
                    Button(
                        onClick = {
                            if (text.isNotBlank() && reference.isNotBlank()) {
                                onSave(text, reference)
                            }
                        },
                        colors = ButtonDefaults.buttonColors(containerColor = ShedBlue),
                        enabled = text.isNotBlank() && reference.isNotBlank()
                    ) { Text("Save") }
                }
            }
        }
    }
}
