package com.graberhub.companion.ui.screens

import androidx.compose.animation.animateColorAsState
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Check
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
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.graberhub.companion.data.models.Chore
import com.graberhub.companion.data.models.Kid
import com.graberhub.companion.data.models.KidConfig
import com.graberhub.companion.ui.components.EmojiPickerDialog
import com.graberhub.companion.ui.theme.*
import com.graberhub.companion.viewmodel.MainViewModel

@Composable
fun ChoresScreen(viewModel: MainViewModel) {
    val kids by viewModel.kids.collectAsStateWithLifecycle()
    val kidsConfig by viewModel.kidsConfig.collectAsStateWithLifecycle()
    val isLoading by viewModel.isLoading.collectAsStateWithLifecycle()
    var showAddDialog by remember { mutableStateOf<Kid?>(null) }
    var editingChore by remember { mutableStateOf<Chore?>(null) }

    Box(modifier = Modifier.fillMaxSize()) {
        if (isLoading && kids.isEmpty()) {
            Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = ShedBlue)
            }
        } else {
            LazyColumn(
                modifier = Modifier.fillMaxSize().padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                item {
                    Text(
                        "✅ Chores",
                        style = MaterialTheme.typography.headlineMedium,
                        color = TextPrimary,
                        fontWeight = FontWeight.Bold
                    )
                }

                items(kids, key = { it.id }) { kid ->
                    val kidColor = kidsConfig.find { it.id == kid.id }?.color ?: kid.color
                    val color = try {
                        Color(android.graphics.Color.parseColor(kidColor))
                    } catch (_: Exception) { ShedBlue }

                    KidChoreCard(
                        kid = kid,
                        kidColor = color,
                        onToggleChore = { viewModel.toggleChore(it.id) },
                        onAddChore = { showAddDialog = kid },
                        onEditChore = { editingChore = it },
                        onDeleteChore = { viewModel.deleteChore(it.id) }
                    )
                }

                item { Spacer(Modifier.height(80.dp)) }
            }
        }
    }

    // Add chore dialog
    showAddDialog?.let { kid ->
        ChoreDialog(
            kidName = kid.name,
            chore = null,
            onSave = { title, emoji ->
                viewModel.addChore(kid.id, title, emoji)
                showAddDialog = null
            },
            onDismiss = { showAddDialog = null }
        )
    }

    // Edit chore dialog
    editingChore?.let { chore ->
        ChoreDialog(
            kidName = null,
            chore = chore,
            onSave = { title, emoji ->
                viewModel.editChore(chore.id, title, emoji)
                editingChore = null
            },
            onDismiss = { editingChore = null }
        )
    }
}

@Composable
private fun KidChoreCard(
    kid: Kid,
    kidColor: Color,
    onToggleChore: (Chore) -> Unit,
    onAddChore: () -> Unit,
    onEditChore: (Chore) -> Unit,
    onDeleteChore: (Chore) -> Unit
) {
    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .shadow(2.dp, RoundedCornerShape(16.dp)),
        shape = RoundedCornerShape(16.dp),
        color = CardBackground
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            // Kid header
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Box(
                        modifier = Modifier
                            .size(36.dp)
                            .clip(CircleShape)
                            .background(kidColor),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            kid.name.first().uppercase(),
                            color = Color.White,
                            fontWeight = FontWeight.Bold,
                            fontSize = 16.sp
                        )
                    }
                    Spacer(Modifier.width(10.dp))
                    Text(
                        kid.name,
                        style = MaterialTheme.typography.titleLarge,
                        color = TextPrimary,
                        fontWeight = FontWeight.SemiBold
                    )
                }
                IconButton(onClick = onAddChore) {
                    Icon(Icons.Default.Add, contentDescription = "Add Chore", tint = kidColor)
                }
            }

            if (kid.chores.isEmpty()) {
                Text(
                    "No chores yet — tap + to add one",
                    style = MaterialTheme.typography.bodyMedium,
                    color = TextHint,
                    modifier = Modifier.padding(vertical = 12.dp, horizontal = 4.dp)
                )
            } else {
                Spacer(Modifier.height(8.dp))
                kid.chores.forEach { chore ->
                    ChoreItem(
                        chore = chore,
                        kidColor = kidColor,
                        onToggle = { onToggleChore(chore) },
                        onEdit = { onEditChore(chore) },
                        onDelete = { onDeleteChore(chore) }
                    )
                }
            }
        }
    }
}

@Composable
private fun ChoreItem(
    chore: Chore,
    kidColor: Color,
    onToggle: () -> Unit,
    onEdit: () -> Unit,
    onDelete: () -> Unit
) {
    val bgColor by animateColorAsState(
        if (chore.done) CheckGreenLight else BackgroundGray,
        label = "choreBg"
    )

    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 3.dp),
        shape = RoundedCornerShape(10.dp),
        color = bgColor
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .clickable { onToggle() }
                .padding(horizontal = 12.dp, vertical = 10.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Checkbox
            Box(
                modifier = Modifier
                    .size(24.dp)
                    .clip(CircleShape)
                    .background(if (chore.done) CheckGreen else Color.Transparent)
                    .then(
                        if (!chore.done) Modifier.background(Color.Transparent)
                            .clip(CircleShape)
                            .background(BorderLight)
                        else Modifier
                    ),
                contentAlignment = Alignment.Center
            ) {
                if (chore.done) {
                    Icon(
                        Icons.Default.Check,
                        contentDescription = null,
                        tint = Color.White,
                        modifier = Modifier.size(16.dp)
                    )
                }
            }

            Spacer(Modifier.width(10.dp))

            // Emoji
            if (chore.emoji.isNotEmpty()) {
                Text(chore.emoji, fontSize = 18.sp)
                Spacer(Modifier.width(6.dp))
            }

            // Title
            Text(
                chore.title,
                style = MaterialTheme.typography.bodyLarge,
                color = if (chore.done) TextSecondary else TextPrimary,
                textDecoration = if (chore.done) TextDecoration.LineThrough else null,
                modifier = Modifier.weight(1f)
            )

            // Edit / Delete
            IconButton(onClick = onEdit, modifier = Modifier.size(28.dp)) {
                Icon(Icons.Default.Edit, contentDescription = "Edit", tint = TextHint, modifier = Modifier.size(16.dp))
            }
            IconButton(onClick = onDelete, modifier = Modifier.size(28.dp)) {
                Icon(Icons.Default.Delete, contentDescription = "Delete", tint = TextHint, modifier = Modifier.size(16.dp))
            }
        }
    }
}

@Composable
private fun ChoreDialog(
    kidName: String?,
    chore: Chore?,
    onSave: (title: String, emoji: String) -> Unit,
    onDismiss: () -> Unit
) {
    var title by remember { mutableStateOf(chore?.title ?: "") }
    var emoji by remember { mutableStateOf(chore?.emoji ?: "") }
    var showEmojiPicker by remember { mutableStateOf(false) }

    Dialog(onDismissRequest = onDismiss) {
        Surface(
            shape = RoundedCornerShape(16.dp),
            color = CardBackground
        ) {
            Column(modifier = Modifier.padding(20.dp)) {
                Text(
                    if (chore != null) "Edit Chore" else "Add Chore for ${kidName ?: ""}",
                    style = MaterialTheme.typography.headlineSmall,
                    color = TextPrimary
                )

                Spacer(Modifier.height(16.dp))

                OutlinedTextField(
                    value = title,
                    onValueChange = { title = it },
                    label = { Text("Chore Name") },
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
                        if (emoji.isNotEmpty()) "$emoji  Tap to change" else "Choose Emoji (optional)",
                        fontSize = if (emoji.isNotEmpty()) 16.sp else 14.sp,
                        color = if (emoji.isNotEmpty()) TextPrimary else TextHint
                    )
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
                            if (title.isNotBlank()) onSave(title, emoji)
                        },
                        colors = ButtonDefaults.buttonColors(containerColor = ShedBlue),
                        enabled = title.isNotBlank()
                    ) { Text("Save") }
                }
            }
        }
    }

    if (showEmojiPicker) {
        EmojiPickerDialog(
            selectedEmoji = emoji,
            onEmojiSelected = { emoji = it },
            onDismiss = { showEmojiPicker = false }
        )
    }
}
