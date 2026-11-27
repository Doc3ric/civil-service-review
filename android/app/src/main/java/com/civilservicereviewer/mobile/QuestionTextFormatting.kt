package com.civilservicereviewer.mobile

import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.font.FontWeight

private val boldQuestionText = Regex("\\*\\*(.+?)\\*\\*", RegexOption.DOT_MATCHES_ALL)

fun formattedQuestionPrompt(prompt: String): AnnotatedString = buildAnnotatedString {
    var nextCharacter = 0

    for (match in boldQuestionText.findAll(prompt)) {
        append(prompt.substring(nextCharacter, match.range.first))
        pushStyle(SpanStyle(fontWeight = FontWeight.ExtraBold))
        append(match.groupValues[1])
        pop()
        nextCharacter = match.range.last + 1
    }

    append(prompt.substring(nextCharacter))
}
