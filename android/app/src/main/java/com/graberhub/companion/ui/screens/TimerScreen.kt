package com.graberhub.companion.ui.screens

import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.graberhub.companion.ui.theme.*
import com.graberhub.companion.viewmodel.MainViewModel

@Composable
fun TimerScreen(viewModel: MainViewModel) {
    val timerStatus by viewModel.timerStatus.collectAsStateWithLifecycle()
    val secondsLeft by viewModel.timerSecondsLeft.collectAsStateWithLifecycle()
    val timerFinished by viewModel.timerFinished.collectAsStateWithLifecycle()
    val alarmPlaying by viewModel.alarmPlaying.collectAsStateWithLifecycle()

    var showTimePicker by remember { mutableStateOf(false) }
    var selectedMinutes by remember { mutableIntStateOf(15) }
    var timerLabel by remember { mutableStateOf("") }

    val isActive = timerStatus?.active == true

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text(
            "⏱️ Timer",
            style = MaterialTheme.typography.headlineMedium,
            color = TextPrimary,
            fontWeight = FontWeight.Bold,
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 24.dp)
        )

        Spacer(Modifier.height(24.dp))

        // Timer display
        if (isActive || timerFinished) {
            // Show countdown
            val minutes = (secondsLeft / 60).toInt()
            val seconds = (secondsLeft % 60).toInt()

            Box(
                modifier = Modifier
                    .size(220.dp)
                    .clip(CircleShape)
                    .background(
                        if (timerFinished) ShedPink.copy(alpha = 0.15f)
                        else ShedBlueLight.copy(alpha = 0.3f)
                    ),
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    if (timerFinished) {
                        // Pulsing animation for finished state
                        val infiniteTransition = rememberInfiniteTransition(label = "pulse")
                        val scale by infiniteTransition.animateFloat(
                            initialValue = 1f,
                            targetValue = 1.15f,
                            animationSpec = infiniteRepeatable(
                                animation = tween(600),
                                repeatMode = RepeatMode.Reverse
                            ),
                            label = "pulseScale"
                        )
                        Text(
                            "⏰",
                            fontSize = (48 * scale).sp
                        )
                        Text(
                            "Time's Up!",
                            style = MaterialTheme.typography.headlineMedium,
                            color = ShedPink,
                            fontWeight = FontWeight.Bold
                        )
                    } else {
                        Text(
                            String.format("%02d:%02d", minutes, seconds),
                            fontSize = 48.sp,
                            fontWeight = FontWeight.Bold,
                            color = ShedBlue
                        )
                        timerStatus?.label?.let { label ->
                            if (label.isNotEmpty()) {
                                Text(
                                    label,
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = TextSecondary
                                )
                            }
                        }
                    }
                }
            }

            Spacer(Modifier.height(32.dp))

            // Action buttons
            if (timerFinished) {
                if (alarmPlaying) {
                    Button(
                        onClick = { viewModel.stopAlarmSound() },
                        colors = ButtonDefaults.buttonColors(containerColor = ShedPink),
                        modifier = Modifier.fillMaxWidth(0.6f),
                        shape = RoundedCornerShape(12.dp)
                    ) {
                        Text("🔇 Stop Alarm", fontSize = 16.sp, modifier = Modifier.padding(vertical = 4.dp))
                    }
                    Spacer(Modifier.height(12.dp))
                }
                Button(
                    onClick = { viewModel.dismissTimerFinished() },
                    colors = ButtonDefaults.buttonColors(containerColor = ShedBlue),
                    modifier = Modifier.fillMaxWidth(0.6f),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Text("✓ Dismiss", fontSize = 16.sp, modifier = Modifier.padding(vertical = 4.dp))
                }
                Spacer(Modifier.height(12.dp))
                OutlinedButton(
                    onClick = { viewModel.cancelTimer() },
                    modifier = Modifier.fillMaxWidth(0.6f),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Text("Reset Timer")
                }
            } else {
                OutlinedButton(
                    onClick = { viewModel.cancelTimer() },
                    modifier = Modifier.fillMaxWidth(0.6f),
                    shape = RoundedCornerShape(12.dp),
                    colors = ButtonDefaults.outlinedButtonColors(contentColor = ShedPink)
                ) {
                    Text("✕ Cancel Timer", fontSize = 16.sp, modifier = Modifier.padding(vertical = 4.dp))
                }
            }
        } else {
            // Timer setup
            Box(
                modifier = Modifier
                    .size(220.dp)
                    .clip(CircleShape)
                    .background(BackgroundGray)
                    .clickable { showTimePicker = true },
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(
                        "$selectedMinutes",
                        fontSize = 56.sp,
                        fontWeight = FontWeight.Bold,
                        color = ShedBlue
                    )
                    Text(
                        "minutes",
                        style = MaterialTheme.typography.bodyLarge,
                        color = TextSecondary
                    )
                    Text(
                        "Tap to change",
                        style = MaterialTheme.typography.bodySmall,
                        color = TextHint
                    )
                }
            }

            Spacer(Modifier.height(24.dp))

            // Quick presets
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceEvenly
            ) {
                listOf(5, 10, 15, 20, 30, 45).forEach { mins ->
                    Surface(
                        modifier = Modifier
                            .clip(RoundedCornerShape(8.dp))
                            .clickable { selectedMinutes = mins },
                        color = if (selectedMinutes == mins) ShedBlue else BackgroundGray,
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            "${mins}m",
                            modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                            color = if (selectedMinutes == mins) Color.White else TextSecondary,
                            fontWeight = FontWeight.Medium,
                            fontSize = 13.sp
                        )
                    }
                }
            }

            Spacer(Modifier.height(20.dp))

            // Label input
            OutlinedTextField(
                value = timerLabel,
                onValueChange = { timerLabel = it },
                label = { Text("Label (optional)") },
                modifier = Modifier.fillMaxWidth(0.8f),
                singleLine = true
            )

            Spacer(Modifier.height(24.dp))

            // Start button
            Button(
                onClick = {
                    viewModel.startTimer(selectedMinutes, timerLabel)
                },
                colors = ButtonDefaults.buttonColors(containerColor = ShedBlue),
                modifier = Modifier.fillMaxWidth(0.6f),
                shape = RoundedCornerShape(12.dp)
            ) {
                Text("▶ Start Timer", fontSize = 16.sp, modifier = Modifier.padding(vertical = 4.dp))
            }
        }
    }

    // Clock-style time picker dialog for timer duration
    if (showTimePicker) {
        TimerDurationPickerDialog(
            initialMinutes = selectedMinutes,
            onMinutesSelected = {
                selectedMinutes = it
                showTimePicker = false
            },
            onDismiss = { showTimePicker = false }
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun TimerDurationPickerDialog(
    initialMinutes: Int,
    onMinutesSelected: (Int) -> Unit,
    onDismiss: () -> Unit
) {
    val initialHour = initialMinutes / 60
    val initialMin = initialMinutes % 60

    val timePickerState = rememberTimePickerState(
        initialHour = initialHour,
        initialMinute = initialMin,
        is24Hour = true
    )

    Dialog(onDismissRequest = onDismiss) {
        Surface(
            shape = RoundedCornerShape(16.dp),
            color = CardBackground
        ) {
            Column(
                modifier = Modifier.padding(20.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text(
                    "Set Timer Duration",
                    style = MaterialTheme.typography.headlineSmall,
                    color = TextPrimary,
                    modifier = Modifier.padding(bottom = 4.dp)
                )

                Text(
                    "Hours : Minutes",
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary,
                    modifier = Modifier.padding(bottom = 16.dp)
                )

                TimePicker(
                    state = timePickerState,
                    colors = TimePickerDefaults.colors(
                        clockDialColor = BackgroundGray,
                        selectorColor = ShedBlue,
                        containerColor = CardBackground,
                        timeSelectorSelectedContainerColor = ShedBlueLight,
                        timeSelectorSelectedContentColor = ShedBlue,
                        timeSelectorUnselectedContainerColor = BackgroundGray,
                        timeSelectorUnselectedContentColor = TextPrimary
                    )
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
                            val totalMinutes = timePickerState.hour * 60 + timePickerState.minute
                            onMinutesSelected(totalMinutes.coerceAtLeast(1))
                        },
                        colors = ButtonDefaults.buttonColors(containerColor = ShedBlue)
                    ) { Text("OK") }
                }
            }
        }
    }
}
