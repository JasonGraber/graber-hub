package com.graber.hub.watch.ui

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.text.BasicTextField
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.SolidColor
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.wear.compose.foundation.lazy.ScalingLazyColumn
import androidx.wear.compose.material.*
import com.graber.hub.watch.network.RetrofitClient
import com.graber.hub.watch.network.SettingsStore
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch

@Composable
fun ServerUrlScreen(onBack: () -> Unit) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()

    var url by remember { mutableStateOf("") }
    var saved by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        url = SettingsStore.serverUrlFlow(context).first()
    }

    ScalingLazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .background(Color.Black),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        item {
            Text(
                text = "Server URL",
                color = Color.White,
                fontSize = 14.sp,
                fontWeight = FontWeight.Bold,
                textAlign = TextAlign.Center,
                modifier = Modifier.padding(bottom = 8.dp),
            )
        }

        item {
            BasicTextField(
                value = url,
                onValueChange = {
                    url = it
                    saved = false
                },
                textStyle = TextStyle(
                    color = Color.White,
                    fontSize = 13.sp,
                    textAlign = TextAlign.Center,
                ),
                cursorBrush = SolidColor(Color(0xFF7C6BF0)),
                keyboardOptions = KeyboardOptions(
                    keyboardType = KeyboardType.Uri,
                    imeAction = ImeAction.Done,
                ),
                keyboardActions = KeyboardActions(
                    onDone = {
                        scope.launch {
                            SettingsStore.setServerUrl(context, url.trim())
                            RetrofitClient.reset()
                            saved = true
                        }
                    },
                ),
                modifier = Modifier
                    .fillMaxWidth()
                    .background(Color(0xFF1A1A2E), shape = androidx.compose.foundation.shape.RoundedCornerShape(8.dp))
                    .padding(horizontal = 12.dp, vertical = 10.dp),
            )
        }

        item {
            Spacer(Modifier.height(8.dp))
            CompactChip(
                onClick = {
                    scope.launch {
                        SettingsStore.setServerUrl(context, url.trim())
                        RetrofitClient.reset()
                        saved = true
                    }
                },
                label = {
                    Text(
                        if (saved) "✓ Saved" else "Save",
                        fontSize = 12.sp,
                        color = Color.White,
                    )
                },
                colors = ChipDefaults.chipColors(
                    backgroundColor = if (saved) Color(0xFF4ADE80).copy(alpha = 0.3f) else Color(0xFF7C6BF0),
                ),
            )
        }

        item {
            Spacer(Modifier.height(4.dp))
            CompactChip(
                onClick = onBack,
                label = {
                    Text("← Back", fontSize = 12.sp, color = Color(0xFFB0B0C8))
                },
                colors = ChipDefaults.chipColors(backgroundColor = Color(0xFF1A1A2E)),
            )
        }
    }
}
