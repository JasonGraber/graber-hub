package com.graberhub.companion.ui.screens

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
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
import com.graberhub.companion.data.models.CreateScheduleRequest
import com.graberhub.companion.data.models.ScheduleBlock
import com.graberhub.companion.ui.components.*
import com.graberhub.companion.ui.theme.*
import com.graberhub.companion.viewmodel.MainViewModel

// Schedule templates
data class ScheduleTemplate(
    val name: String,
    val emoji: String,
    val blocks: List<CreateScheduleRequest>
)

val scheduleTemplates = listOf(
    ScheduleTemplate("School Day", "🏫", listOf(
        CreateScheduleRequest("06:30", "07:00", "Wake Up & Get Ready", "🌅"),
        CreateScheduleRequest("07:00", "07:30", "Breakfast", "🥣"),
        CreateScheduleRequest("07:30", "08:00", "Bus / Drive to School", "🚌"),
        CreateScheduleRequest("08:00", "15:00", "School", "📚"),
        CreateScheduleRequest("15:00", "15:30", "Snack & Unwind", "🍎"),
        CreateScheduleRequest("15:30", "17:00", "Homework & Reading", "📖"),
        CreateScheduleRequest("17:00", "18:00", "Free Time / Play", "🎮"),
        CreateScheduleRequest("18:00", "18:30", "Dinner", "🍽️"),
        CreateScheduleRequest("18:30", "19:30", "Family Time", "👨‍👩‍👧‍👦"),
        CreateScheduleRequest("19:30", "20:00", "Get Ready for Bed", "🛁"),
        CreateScheduleRequest("20:00", "20:30", "Bible / Devotion", "📖"),
        CreateScheduleRequest("20:30", "06:30", "Sleep", "😴")
    )),
    ScheduleTemplate("Weekend", "🌞", listOf(
        CreateScheduleRequest("07:30", "08:00", "Wake Up", "🌅"),
        CreateScheduleRequest("08:00", "08:30", "Breakfast", "🥞"),
        CreateScheduleRequest("08:30", "09:00", "Chores", "🧹"),
        CreateScheduleRequest("09:00", "12:00", "Free Time / Activities", "🎨"),
        CreateScheduleRequest("12:00", "12:30", "Lunch", "🥪"),
        CreateScheduleRequest("12:30", "17:00", "Outdoor / Family Activity", "🌳"),
        CreateScheduleRequest("17:00", "18:00", "Free Time", "🎮"),
        CreateScheduleRequest("18:00", "18:30", "Dinner", "🍽️"),
        CreateScheduleRequest("18:30", "20:00", "Movie / Game Night", "🎬"),
        CreateScheduleRequest("20:00", "20:30", "Get Ready for Bed", "🛁"),
        CreateScheduleRequest("20:30", "07:30", "Sleep", "😴")
    )),
    ScheduleTemplate("Summer Day", "☀️", listOf(
        CreateScheduleRequest("08:00", "08:30", "Wake Up", "🌅"),
        CreateScheduleRequest("08:30", "09:00", "Breakfast", "🥞"),
        CreateScheduleRequest("09:00", "10:00", "Reading Time", "📚"),
        CreateScheduleRequest("10:00", "12:00", "Outdoor Play", "🏊"),
        CreateScheduleRequest("12:00", "12:30", "Lunch", "🥪"),
        CreateScheduleRequest("12:30", "14:00", "Quiet Time / Rest", "😌"),
        CreateScheduleRequest("14:00", "16:00", "Creative Activity", "🎨"),
        CreateScheduleRequest("16:00", "18:00", "Free Play", "⚽"),
        CreateScheduleRequest("18:00", "18:30", "Dinner", "🍽️"),
        CreateScheduleRequest("18:30", "20:00", "Family Time", "👨‍👩‍👧‍👦"),
        CreateScheduleRequest("20:00", "20:30", "Bible / Devotion", "📖"),
        CreateScheduleRequest("20:30", "08:00", "Sleep", "😴")
    ))
)

@Composable
fun ScheduleScreen(viewModel: MainViewModel) {
    val schedule by viewModel.schedule.collectAsStateWithLifecycle()
    val isLoading by viewModel.isLoading.collectAsStateWithLifecycle()
    var showAddDialog by remember { mutableStateOf(false) }
    var editingBlock by remember { mutableStateOf<ScheduleBlock?>(null) }
    var showTemplateDialog by remember { mutableStateOf(false) }

    Box(modifier = Modifier.fillMaxSize()) {
        Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
            // Header row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    "📅 Today's Schedule",
                    style = MaterialTheme.typography.headlineMedium,
                    color = TextPrimary,
                    fontWeight = FontWeight.Bold
                )
                Row {
                    TextButton(onClick = { showTemplateDialog = true }) {
                        Text("📋 Templates")
                    }
                }
            }

            Spacer(Modifier.height(12.dp))

            if (isLoading && schedule.isEmpty()) {
                Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = ShedBlue)
                }
            } else if (schedule.isEmpty()) {
                Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("📅", fontSize = 48.sp)
                        Spacer(Modifier.height(8.dp))
                        Text("No schedule blocks yet", color = TextSecondary)
                        Spacer(Modifier.height(8.dp))
                        TextButton(onClick = { showTemplateDialog = true }) {
                            Text("Apply a template to get started")
                        }
                    }
                }
            } else {
                LazyColumn(
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                    contentPadding = PaddingValues(bottom = 80.dp),
                    modifier = Modifier.weight(1f)
                ) {
                    itemsIndexed(schedule, key = { _, block -> block.id }) { _, block ->
                        ScheduleBlockCard(
                            block = block,
                            onEdit = { editingBlock = block },
                            onDelete = { viewModel.deleteScheduleBlock(block.id) }
                        )
                    }
                }
            }
        }

        // FAB
        FloatingActionButton(
            onClick = { showAddDialog = true },
            containerColor = ShedBlue,
            contentColor = Color.White,
            modifier = Modifier
                .align(Alignment.BottomEnd)
                .padding(16.dp)
        ) {
            Icon(Icons.Default.Add, contentDescription = "Add Schedule Block")
        }
    }

    // Add dialog
    if (showAddDialog) {
        ScheduleBlockDialog(
            block = null,
            onSave = { title, emoji, startH, startM, endH, endM ->
                viewModel.addScheduleBlock(
                    formatTime24(startH, startM),
                    formatTime24(endH, endM),
                    title, emoji
                )
                showAddDialog = false
            },
            onDismiss = { showAddDialog = false }
        )
    }

    // Edit dialog
    editingBlock?.let { block ->
        val (sh, sm) = parseTimeString(block.time_start)
        val (eh, em) = parseTimeString(block.time_end)
        ScheduleBlockDialog(
            block = block,
            onSave = { title, emoji, startH, startM, endH, endM ->
                viewModel.editScheduleBlock(
                    block.id, title, emoji,
                    formatTime24(startH, startM),
                    formatTime24(endH, endM)
                )
                editingBlock = null
            },
            onDismiss = { editingBlock = null }
        )
    }

    // Template dialog
    if (showTemplateDialog) {
        TemplatePickerDialog(
            onApply = { template ->
                // Delete existing and apply template
                schedule.forEach { viewModel.deleteScheduleBlock(it.id) }
                template.blocks.forEach { block ->
                    viewModel.addScheduleBlock(block.time_start, block.time_end, block.title, block.emoji)
                }
                showTemplateDialog = false
            },
            onSaveAsTemplate = {
                viewModel.applyTemplate()
                showTemplateDialog = false
            },
            onDismiss = { showTemplateDialog = false }
        )
    }
}

@Composable
private fun ScheduleBlockCard(
    block: ScheduleBlock,
    onEdit: () -> Unit,
    onDelete: () -> Unit
) {
    val (sh, sm) = parseTimeString(block.time_start)
    val (eh, em) = parseTimeString(block.time_end)

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
                .padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Time column
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                modifier = Modifier.width(72.dp)
            ) {
                Text(
                    formatTime(sh, sm),
                    style = MaterialTheme.typography.labelMedium,
                    color = ShedBlue,
                    fontWeight = FontWeight.SemiBold
                )
                Text("│", color = TextHint, fontSize = 10.sp)
                Text(
                    formatTime(eh, em),
                    style = MaterialTheme.typography.labelMedium,
                    color = TextSecondary
                )
            }

            Spacer(Modifier.width(12.dp))

            // Emoji
            if (block.emoji.isNotEmpty()) {
                Text(block.emoji, fontSize = 24.sp)
                Spacer(Modifier.width(8.dp))
            }

            // Title
            Text(
                block.title,
                style = MaterialTheme.typography.titleMedium,
                color = TextPrimary,
                modifier = Modifier.weight(1f)
            )

            // Actions
            IconButton(onClick = onEdit, modifier = Modifier.size(32.dp)) {
                Icon(Icons.Default.Edit, contentDescription = "Edit", tint = TextHint, modifier = Modifier.size(18.dp))
            }
            IconButton(onClick = onDelete, modifier = Modifier.size(32.dp)) {
                Icon(Icons.Default.Delete, contentDescription = "Delete", tint = TextHint, modifier = Modifier.size(18.dp))
            }
        }
    }
}

@Composable
private fun ScheduleBlockDialog(
    block: ScheduleBlock?,
    onSave: (title: String, emoji: String, startH: Int, startM: Int, endH: Int, endM: Int) -> Unit,
    onDismiss: () -> Unit
) {
    val initialStart = block?.let { parseTimeString(it.time_start) } ?: Pair(8, 0)
    val initialEnd = block?.let { parseTimeString(it.time_end) } ?: Pair(9, 0)

    var title by remember { mutableStateOf(block?.title ?: "") }
    var emoji by remember { mutableStateOf(block?.emoji ?: "") }
    var startHour by remember { mutableIntStateOf(initialStart.first) }
    var startMinute by remember { mutableIntStateOf(initialStart.second) }
    var endHour by remember { mutableIntStateOf(initialEnd.first) }
    var endMinute by remember { mutableIntStateOf(initialEnd.second) }
    var showStartTimePicker by remember { mutableStateOf(false) }
    var showEndTimePicker by remember { mutableStateOf(false) }
    var showEmojiPicker by remember { mutableStateOf(false) }

    Dialog(onDismissRequest = onDismiss) {
        Surface(
            shape = RoundedCornerShape(16.dp),
            color = CardBackground
        ) {
            Column(modifier = Modifier.padding(20.dp)) {
                Text(
                    if (block != null) "Edit Schedule Block" else "Add Schedule Block",
                    style = MaterialTheme.typography.headlineSmall,
                    color = TextPrimary
                )

                Spacer(Modifier.height(16.dp))

                OutlinedTextField(
                    value = title,
                    onValueChange = { title = it },
                    label = { Text("Activity Name") },
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
                    Text(
                        if (emoji.isNotEmpty()) emoji else "Choose Emoji",
                        fontSize = if (emoji.isNotEmpty()) 24.sp else 14.sp,
                        color = if (emoji.isNotEmpty()) TextPrimary else TextHint
                    )
                }

                Spacer(Modifier.height(12.dp))

                // Time pickers
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceEvenly
                ) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("Start", style = MaterialTheme.typography.labelMedium, color = TextSecondary)
                        TextButton(onClick = { showStartTimePicker = true }) {
                            Text(
                                formatTime(startHour, startMinute),
                                style = MaterialTheme.typography.titleLarge,
                                color = ShedBlue
                            )
                        }
                    }
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("End", style = MaterialTheme.typography.labelMedium, color = TextSecondary)
                        TextButton(onClick = { showEndTimePicker = true }) {
                            Text(
                                formatTime(endHour, endMinute),
                                style = MaterialTheme.typography.titleLarge,
                                color = ShedBlue
                            )
                        }
                    }
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
                            if (title.isNotBlank()) {
                                onSave(title, emoji, startHour, startMinute, endHour, endMinute)
                            }
                        },
                        colors = ButtonDefaults.buttonColors(containerColor = ShedBlue),
                        enabled = title.isNotBlank()
                    ) { Text("Save") }
                }
            }
        }
    }

    if (showStartTimePicker) {
        ClockTimePickerDialog(
            initialHour = startHour,
            initialMinute = startMinute,
            onTimeSelected = { h, m ->
                startHour = h; startMinute = m
                showStartTimePicker = false
            },
            onDismiss = { showStartTimePicker = false }
        )
    }

    if (showEndTimePicker) {
        ClockTimePickerDialog(
            initialHour = endHour,
            initialMinute = endMinute,
            onTimeSelected = { h, m ->
                endHour = h; endMinute = m
                showEndTimePicker = false
            },
            onDismiss = { showEndTimePicker = false }
        )
    }

    if (showEmojiPicker) {
        EmojiPickerDialog(
            selectedEmoji = emoji,
            onEmojiSelected = { emoji = it },
            onDismiss = { showEmojiPicker = false }
        )
    }
}

@Composable
private fun TemplatePickerDialog(
    onApply: (ScheduleTemplate) -> Unit,
    onSaveAsTemplate: () -> Unit,
    onDismiss: () -> Unit
) {
    Dialog(onDismissRequest = onDismiss) {
        Surface(
            shape = RoundedCornerShape(16.dp),
            color = CardBackground
        ) {
            Column(modifier = Modifier.padding(20.dp)) {
                Text(
                    "Schedule Templates",
                    style = MaterialTheme.typography.headlineSmall,
                    color = TextPrimary
                )

                Spacer(Modifier.height(16.dp))

                scheduleTemplates.forEach { template ->
                    Surface(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 4.dp)
                            .clickable { onApply(template) },
                        shape = RoundedCornerShape(12.dp),
                        color = BackgroundGray
                    ) {
                        Row(
                            modifier = Modifier.padding(16.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(template.emoji, fontSize = 28.sp)
                            Spacer(Modifier.width(12.dp))
                            Column {
                                Text(
                                    template.name,
                                    style = MaterialTheme.typography.titleMedium,
                                    color = TextPrimary
                                )
                                Text(
                                    "${template.blocks.size} blocks",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = TextSecondary
                                )
                            }
                        }
                    }
                }

                Spacer(Modifier.height(12.dp))
                HorizontalDivider(color = DividerColor)
                Spacer(Modifier.height(8.dp))

                TextButton(
                    onClick = onSaveAsTemplate,
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("💾 Save Current as Template")
                }

                TextButton(
                    onClick = onDismiss,
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("Cancel")
                }
            }
        }
    }
}
