package com.graberhub.companion.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable

private val GraberColorScheme = lightColorScheme(
    primary = ShedBlue,
    onPrimary = BackgroundWhite,
    primaryContainer = ShedBlueLight,
    onPrimaryContainer = ShedBlueDark,
    secondary = ShedPink,
    onSecondary = BackgroundWhite,
    secondaryContainer = ShedPinkLight,
    onSecondaryContainer = ShedPink,
    tertiary = KidPurple,
    background = BackgroundGray,
    onBackground = TextPrimary,
    surface = SurfaceWhite,
    onSurface = TextPrimary,
    surfaceVariant = BackgroundGray,
    onSurfaceVariant = TextSecondary,
    outline = BorderLight,
    outlineVariant = DividerColor
)

@Composable
fun GraberHubTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = GraberColorScheme,
        typography = GraberTypography,
        content = content
    )
}
