package com.civilservicereviewer.mobile

import androidx.compose.animation.core.RepeatMode
import androidx.compose.animation.core.animateFloat
import androidx.compose.animation.core.infiniteRepeatable
import androidx.compose.animation.core.rememberInfiniteTransition
import androidx.compose.animation.core.tween
import androidx.compose.foundation.Canvas
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.drawscope.withTransform
import androidx.compose.ui.graphics.vector.PathParser
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics

private const val outerFlamePath = "M64 8 C70 30 98 44 98 78 C98 102 82 118 64 118 C46 118 30 102 30 78 C30 62 38 52 46 44 C46 56 52 62 58 62 C54 44 56 24 64 8 Z"
private const val middleFlamePath = "M64 44 C68 60 84 68 84 88 C84 104 74 114 64 114 C54 114 44 104 44 88 C44 76 52 70 56 62 C57 70 60 72 62 72 C60 62 60 52 64 44 Z"
private const val coreFlamePath = "M64 74 C67 84 74 88 74 99 C74 108 69 113 64 113 C59 113 54 108 54 99 C54 92 60 88 64 74 Z"

@Composable
fun AnimatedStreakIcon(modifier: Modifier = Modifier) {
    val outerPath = remember { PathParser().parsePathString(outerFlamePath).toPath() }
    val middlePath = remember { PathParser().parsePathString(middleFlamePath).toPath() }
    val corePath = remember { PathParser().parsePathString(coreFlamePath).toPath() }
    val animation = rememberInfiniteTransition(label = "streak flame")
    val sway by animation.animateFloat(
        initialValue = -1f,
        targetValue = 1f,
        animationSpec = infiniteRepeatable(tween(1100), RepeatMode.Reverse),
        label = "flame sway",
    )
    val flicker by animation.animateFloat(
        initialValue = 0f,
        targetValue = 1f,
        animationSpec = infiniteRepeatable(tween(800), RepeatMode.Reverse),
        label = "flame flicker",
    )
    val sparkRise by animation.animateFloat(
        initialValue = 0f,
        targetValue = 1f,
        animationSpec = infiniteRepeatable(tween(1800)),
        label = "rising sparks",
    )

    Canvas(modifier.semantics { contentDescription = "Animated fire streak" }) {
        val scaleX = size.width / 128f
        val scaleY = size.height / 128f
        withTransform({ scale(scaleX, scaleY, pivot = Offset.Zero) }) {
            drawOval(
                brush = Brush.radialGradient(
                    0f to Color(0x8CFF8A00),
                    1f to Color.Transparent,
                    center = Offset(64f, 78f),
                    radius = 52f,
                ),
                topLeft = Offset(12f, 30f),
                size = Size(104f, 96f),
                alpha = 0.8f + flicker * 0.2f,
            )

            withTransform({ scale(1f + sway * 0.025f, 1f + flicker * 0.025f, Offset(64f, 118f)) }) {
                drawPath(
                    outerPath,
                    Brush.verticalGradient(
                        0f to Color(0xFFFFA51F),
                        0.4f to Color(0xFFFF7A00),
                        1f to Color(0xFFFF3D00),
                        startY = 8f,
                        endY = 118f,
                    ),
                )
            }
            withTransform({ scale(1f - sway * 0.03f, 1f + flicker * 0.06f, Offset(64f, 114f)) }) {
                drawPath(middlePath, Brush.verticalGradient(listOf(Color(0xFFFFD23F), Color(0xFFFF9100)), startY = 44f, endY = 114f))
            }
            withTransform({ scale(1f - flicker * 0.04f, 0.94f + flicker * 0.12f, Offset(64f, 113f)) }) {
                drawPath(corePath, Brush.verticalGradient(listOf(Color(0xFFFFFBE6), Color(0xFFFFE066)), startY = 74f, endY = 113f))
            }

            listOf(
                Triple(Offset(48f, 40f), 0f, Color(0xFFFFD23F)),
                Triple(Offset(82f, 46f), 0.33f, Color(0xFFFFB300)),
                Triple(Offset(66f, 30f), 0.66f, Color(0xFFFFF3B0)),
            ).forEach { (start, delay, color) ->
                val progress = (sparkRise + delay) % 1f
                val opacity = minOf(1f, progress * 6f, (1f - progress) * 4f).coerceAtLeast(0f)
                drawCircle(
                    color = color.copy(alpha = opacity),
                    radius = 2.5f - progress * 1.5f,
                    center = Offset(start.x + (delay - 0.33f) * 14f * progress, start.y - 34f * progress),
                )
            }
        }
    }
}
