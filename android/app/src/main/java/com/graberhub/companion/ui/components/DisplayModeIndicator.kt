package com.graberhub.companion.ui.components

import androidx.compose.animation.animateColorAsState
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.scale
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

private val DashboardBlue = Color(0xFF03A9F4)
private val PhotosPink = Color(0xFFEC5281)
private val AutoGreen = Color(0xFF4ADE80)

data class DisplayModeInfo(
    val label: String,
    val icon: String,
    val color: Color,
    val bgColor: Color,
    val borderColor: Color
)

private fun getModeInfo(mode: String): DisplayModeInfo = when (mode) {
    "photos" -> DisplayModeInfo(
        label = "PHOTOS",
        icon = "📷",
        color = PhotosPink,
        bgColor = PhotosPink.copy(alpha = 0.08f),
        borderColor = PhotosPink.copy(alpha = 0.25f)
    )
    "auto" -> DisplayModeInfo(
        label = "AUTO",
        icon = "✨",
        color = AutoGreen,
        bgColor = AutoGreen.copy(alpha = 0.08f),
        borderColor = AutoGreen.copy(alpha = 0.25f)
    )
    else -> DisplayModeInfo(
        label = "DASHBOARD",
        icon = "🖥️",
        color = DashboardBlue,
        bgColor = DashboardBlue.copy(alpha = 0.08f),
        borderColor = DashboardBlue.copy(alpha = 0.25f)
    )
}

@Composable
fun DisplayModeIndicator(
    mode: String,
    modifier: Modifier = Modifier
) {
    val info = getModeInfo(mode)

    val animatedBgColor by animateColorAsState(
        targetValue = info.bgColor,
        animationSpec = tween(400),
        label = "bgColor"
    )
    val animatedBorderColor by animateColorAsState(
        targetValue = info.borderColor,
        animationSpec = tween(400),
        label = "borderColor"
    )
    val animatedTextColor by animateColorAsState(
        targetValue = info.color,
        animationSpec = tween(400),
        label = "textColor"
    )

    // Pulsing dot animation
    val infiniteTransition = rememberInfiniteTransition(label = "pulse")
    val pulseScale by infiniteTransition.animateFloat(
        initialValue = 0.8f,
        targetValue = 1.2f,
        animationSpec = infiniteRepeatable(
            animation = tween(1000, easing = EaseInOut),
            repeatMode = RepeatMode.Reverse
        ),
        label = "pulseScale"
    )
    val pulseAlpha by infiniteTransition.animateFloat(
        initialValue = 0.6f,
        targetValue = 1.0f,
        animationSpec = infiniteRepeatable(
            animation = tween(1000, easing = EaseInOut),
            repeatMode = RepeatMode.Reverse
        ),
        label = "pulseAlpha"
    )

    Row(
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 6.dp)
            .clip(RoundedCornerShape(12.dp))
            .background(animatedBgColor)
            .border(1.dp, animatedBorderColor, RoundedCornerShape(12.dp))
            .padding(horizontal = 14.dp, vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.Center
    ) {
        // Pulsing dot
        Box(
            modifier = Modifier
                .size(8.dp)
                .scale(pulseScale)
                .clip(CircleShape)
                .background(animatedTextColor.copy(alpha = pulseAlpha))
        )

        Spacer(Modifier.width(10.dp))

        // Mode icon
        Text(
            text = info.icon,
            fontSize = 14.sp
        )

        Spacer(Modifier.width(8.dp))

        // Mode label
        Text(
            text = "${info.label} MODE",
            style = MaterialTheme.typography.labelMedium,
            color = animatedTextColor,
            fontWeight = FontWeight.Bold,
            letterSpacing = 1.5.sp,
            fontSize = 12.sp
        )
    }
}
