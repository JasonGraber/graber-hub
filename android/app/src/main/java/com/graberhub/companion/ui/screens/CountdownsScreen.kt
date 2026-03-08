package com.graberhub.companion.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Delete
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
import com.graberhub.companion.data.models.Countdown
import com.graberhub.companion.ui.components.EmojiPickerDialog
import com.graberhub.companion.ui.theme.*
import com.graberhub.companion.viewmodel.MainViewModel
import java.text.SimpleDateFormat
import java.util.*
import java.util.concurrent.TimeUnit

@Composable
fun CountdownsScreen(viewModel: MainViewModel) {
    val countdowns by viewModel.countdowns.collectAsStateWithLifecycle()
    var showAddDialog by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) { viewModel.loadCountdowns() }

    Box(modifier = Modifier.fillMaxSize()) {
        Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
            Text(
                "⏳ Countdowns",
                style = MaterialTheme.typography.headlineMedium,
                color = TextPrimary,
                fontWeight = FontWeight.Bold
            )

            Spacer(Modifier.height(12.dp))

            if (countdowns.isEmpty()) {
                Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("⏳", fontSize = 48.sp)
                        Spacer(Modifier.height(8.dp))
                        Text("No countdowns yet", color = TextSecondary)
                    }
                }
            } else {
                LazyColumn(
                    verticalArrangement = Arrangement.spacedBy(10.dp),
                    contentPadding = PaddingValues(bottom = 80.dp),
                    modifier = Modifier.weight(1f)
                ) {
                    items(countdowns, key = { it.id }) { countdown ->
                        CountdownCard(
                            countdown = countdown,
                            onDelete = { viewModel.deleteCountdown(countdown.id) }
                        )
                    }
                    item { Spacer(Modifier.height(80.dp)) }
                }
            }
        }

        FloatingActionButton(
            onClick = { showAddDialog = true },
            containerColor = ShedBlue,
            contentColor = Color.White,
            modifier = Modifier.align(Alignment.BottomEnd).padding(16.dp)
        ) {
            Icon(Icons.Default.Add, contentDescription = "Add Countdown")
        }
    }

    if (showAddDialog) {
        AddCountdownDialog(
            onSave = { label, emoji, date ->
                viewModel.addCountdown(label, emoji, date)
                showAddDialog = false
            },
            onDismiss = { showAddDialog = false }
        )
    }
}

@Composable
private fun CountdownCard(countdown: Countdown, onDelete: () -> Unit) {
    val daysLeft = remember(countdown.target_date) {
        try {
            val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.US)
            val target = sdf.parse(countdown.target_date) ?: return@remember 0L
            val today = Calendar.getInstance().apply {
                set(Calendar.HOUR_OF_DAY, 0)
                set(Calendar.MINUTE, 0)
                set(Calendar.SECOND, 0)
                set(Calendar.MILLISECOND, 0)
            }.time
            val diff = target.time - today.time
            TimeUnit.MILLISECONDS.toDays(diff)
        } catch (_: Exception) { 0L }
    }

    val isPast = daysLeft < 0
    val isToday = daysLeft == 0L

    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .shadow(2.dp, RoundedCornerShape(12.dp)),
        shape = RoundedCornerShape(12.dp),
        color = CardBackground
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Emoji
            Text(countdown.emoji, fontSize = 32.sp)

            Spacer(Modifier.width(12.dp))

            Column(modifier = Modifier.weight(1f)) {
                Text(
                    countdown.label,
                    style = MaterialTheme.typography.titleMedium,
                    color = TextPrimary,
                    fontWeight = FontWeight.SemiBold
                )
                Text(
                    countdown.target_date,
                    style = MaterialTheme.typography.bodySmall,
                    color = TextSecondary
                )
            }

            // Days counter
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Text(
                    when {
                        isToday -> "🎉"
                        isPast -> "✓"
                        else -> "$daysLeft"
                    },
                    fontSize = if (isToday || isPast) 24.sp else 28.sp,
                    fontWeight = FontWeight.Bold,
                    color = when {
                        isToday -> ShedPink
                        isPast -> CheckGreen
                        daysLeft <= 7 -> ShedPink
                        else -> ShedBlue
                    }
                )
                if (!isToday && !isPast) {
                    Text(
                        "days",
                        style = MaterialTheme.typography.labelSmall,
                        color = TextSecondary
                    )
                }
            }

            Spacer(Modifier.width(8.dp))

            IconButton(onClick = onDelete, modifier = Modifier.size(32.dp)) {
                Icon(Icons.Default.Delete, contentDescription = "Delete", tint = TextHint, modifier = Modifier.size(18.dp))
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun AddCountdownDialog(
    onSave: (label: String, emoji: String, date: String) -> Unit,
    onDismiss: () -> Unit
) {
    var label by remember { mutableStateOf("") }
    var emoji by remember { mutableStateOf("🎉") }
    var showEmojiPicker by remember { mutableStateOf(false) }

    // Date picker state
    val calendar = remember { Calendar.getInstance().apply { add(Calendar.DAY_OF_YEAR, 7) } }
    var year by remember { mutableIntStateOf(calendar.get(Calendar.YEAR)) }
    var month by remember { mutableIntStateOf(calendar.get(Calendar.MONTH)) }
    var day by remember { mutableIntStateOf(calendar.get(Calendar.DAY_OF_MONTH)) }
    var showDatePicker by remember { mutableStateOf(false) }

    val dateString = String.format("%04d-%02d-%02d", year, month + 1, day)

    Dialog(onDismissRequest = onDismiss) {
        Surface(
            shape = RoundedCornerShape(16.dp),
            color = CardBackground
        ) {
            Column(modifier = Modifier.padding(20.dp)) {
                Text(
                    "Add Countdown",
                    style = MaterialTheme.typography.headlineSmall,
                    color = TextPrimary
                )

                Spacer(Modifier.height(16.dp))

                OutlinedTextField(
                    value = label,
                    onValueChange = { label = it },
                    label = { Text("Event Name") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true
                )

                Spacer(Modifier.height(12.dp))

                // Emoji selector
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(8.dp))
                        .background(BackgroundGray)
                        .clickable { showEmojiPicker = true }
                        .padding(12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("$emoji  Tap to change emoji", color = TextPrimary)
                }

                Spacer(Modifier.height(12.dp))

                // Date selector
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(8.dp))
                        .background(BackgroundGray)
                        .clickable { showDatePicker = true }
                        .padding(12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("📅  $dateString", color = ShedBlue, fontWeight = FontWeight.Medium)
                }

                Spacer(Modifier.height(16.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End
                ) {
                    TextButton(onClick = onDismiss) { Text("Cancel") }
                    Spacer(Modifier.width(8.dp))
                    Button(
                        onClick = {
                            if (label.isNotBlank()) onSave(label, emoji, dateString)
                        },
                        colors = ButtonDefaults.buttonColors(containerColor = ShedBlue),
                        enabled = label.isNotBlank()
                    ) { Text("Save") }
                }
            }
        }
    }

    if (showDatePicker) {
        val datePickerState = rememberDatePickerState(
            initialSelectedDateMillis = Calendar.getInstance().apply {
                set(year, month, day)
            }.timeInMillis
        )
        DatePickerDialog(
            onDismissRequest = { showDatePicker = false },
            confirmButton = {
                TextButton(onClick = {
                    datePickerState.selectedDateMillis?.let { millis ->
                        val cal = Calendar.getInstance().apply { timeInMillis = millis }
                        year = cal.get(Calendar.YEAR)
                        month = cal.get(Calendar.MONTH)
                        day = cal.get(Calendar.DAY_OF_MONTH)
                    }
                    showDatePicker = false
                }) { Text("OK") }
            },
            dismissButton = {
                TextButton(onClick = { showDatePicker = false }) { Text("Cancel") }
            }
        ) {
            DatePicker(state = datePickerState)
        }
    }

    if (showEmojiPicker) {
        EmojiPickerDialog(
            selectedEmoji = emoji,
            onEmojiSelected = { if (it.isNotEmpty()) emoji = it },
            onDismiss = { showEmojiPicker = false }
        )
    }
}
