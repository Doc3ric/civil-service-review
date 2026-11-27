package com.civilservicereviewer.mobile

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import org.json.JSONArray
import org.json.JSONObject
import java.time.Instant
import java.time.LocalDate
import java.time.ZoneId

private val ProgressNavy = Color(0xFF174A8B)
private val ProgressInk = Color(0xFF1E293B)
private val ProgressMuted = Color(0xFF64748B)
private val ProgressRed = Color(0xFFC92535)

private data class AreaAccuracy(
    val code: String,
    val name: String,
    val attempted: Int,
    val correct: Int,
    val color: Color,
) {
    val percentage: Double
        get() = if (attempted == 0) 0.0 else correct * 100.0 / attempted
}

private data class TopicAccuracy(
    val categoryCode: String,
    val categoryName: String,
    val name: String,
    val attempted: Int,
    val correct: Int,
) {
    val percentage: Double
        get() = if (attempted == 0) 0.0 else correct * 100.0 / attempted
}

data class MissedQuestion(
    val localQuestion: LocalQuestion,
    val selectedAnswer: String?,
    val flagged: Boolean,
)

@Composable
fun ProgressPage(
    attempts: List<PracticeAttempt>,
    settings: LocalExamSettings?,
    onReviewMissed: () -> Unit,
    onFocusArea: (String, String) -> Unit,
) {
    val snapshot = remember(settings?.progressSnapshotJson) {
        runCatching { JSONObject(settings?.progressSnapshotJson ?: "{}") }.getOrDefault(JSONObject())
    }
    val timezone = remember(snapshot) {
        runCatching { ZoneId.of(snapshot.optString("timezone", ZoneId.systemDefault().id)) }
            .getOrDefault(ZoneId.systemDefault())
    }
    val categories = remember(snapshot, attempts) { combineCategoryAccuracy(snapshot, attempts) }
    val topics = remember(snapshot, attempts) { combineTopicAccuracy(snapshot, attempts) }
    val studyDates = remember(snapshot, attempts, timezone) {
        buildSet {
            val remoteDates = snapshot.optJSONArray("study_dates") ?: JSONArray()
            for (index in 0 until remoteDates.length()) {
                runCatching { add(LocalDate.parse(remoteDates.optString(index))) }
            }
            attempts.forEach { attempt ->
                runCatching { add(Instant.ofEpochMilli(attempt.completedAt).atZone(timezone).toLocalDate()) }
            }
        }
    }
    val streaks = remember(studyDates) { calculateStreaks(studyDates, LocalDate.now(timezone)) }
    val today = LocalDate.now(timezone)
    val unsyncedTodayQuestions = attempts.filter { !it.synced }.sumOf { attempt ->
        if (runCatching { Instant.ofEpochMilli(attempt.completedAt).atZone(timezone).toLocalDate() == today }.getOrDefault(false)) {
            attempt.questionCount
        } else {
            0
        }
    }
    val dailyTarget = snapshot.optInt("daily_question_target", 20).coerceAtLeast(1)
    val todayQuestions = snapshot.optInt("today_questions_count", 0) + unsyncedTodayQuestions
    val totalSessions = snapshot.optInt("total_sessions", 0) + attempts.count { !it.synced }
    val remoteAverage = snapshot.optDouble("average_score", 0.0)
    val remoteSessionCount = snapshot.optInt("total_sessions", 0)
    val localPending = attempts.filter { !it.synced }
    val weightedScore = remoteAverage * remoteSessionCount + localPending.sumOf { it.scorePercentage }
    val averageScore = if (totalSessions == 0) 0.0 else weightedScore / totalSessions
    val weakestCategory = categories.filter { it.attempted > 0 }.minByOrNull { it.percentage }
    val weakestTopic = topics.filter { it.attempted >= 5 }.minByOrNull { it.percentage }
        ?: topics.filter { it.attempted > 0 }.minByOrNull { it.percentage }
    val missedCount = remember(attempts, snapshot) { allMissedQuestions(attempts, snapshot).size }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(start = 18.dp, top = 18.dp, end = 18.dp, bottom = 24.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        item {
            Column(Modifier.fillMaxWidth().background(ProgressNavy, RoundedCornerShape(22.dp)).padding(20.dp)) {
                Text("STUDY DASHBOARD", color = Color(0xFFBFDBFE), style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, letterSpacing = 1.sp)
                Text("Your progress", color = Color.White, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold, modifier = Modifier.padding(top = 5.dp))
                Text("Your progress is saved on this phone and combined with web results after sync.", color = Color(0xFFE2E8F0), style = MaterialTheme.typography.bodySmall, lineHeight = 19.sp, modifier = Modifier.padding(top = 5.dp))
            }
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                ProgressMetric("CURRENT STREAK", "${streaks.first} days", Modifier.weight(1f))
                ProgressMetric("LONGEST STREAK", "${streaks.second} days", Modifier.weight(1f))
            }
        }
        item {
            ProgressCard {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                    Text("Daily question goal", modifier = Modifier.weight(1f).padding(end = 8.dp), color = ProgressInk, fontWeight = FontWeight.Bold)
                    Text("${todayQuestions.coerceAtMost(dailyTarget)} / $dailyTarget", color = ProgressNavy, fontWeight = FontWeight.Bold, maxLines = 1, softWrap = false)
                }
                LinearProgressIndicator(
                    progress = { (todayQuestions.toFloat() / dailyTarget).coerceIn(0f, 1f) },
                    modifier = Modifier.fillMaxWidth().padding(top = 11.dp).height(8.dp),
                    color = if (todayQuestions >= dailyTarget) Color(0xFF15803D) else ProgressNavy,
                    trackColor = Color(0xFFE2E8F0),
                )
                Text(if (todayQuestions >= dailyTarget) "Daily goal reached" else "${(dailyTarget - todayQuestions).coerceAtLeast(0)} more questions to reach today’s goal", color = ProgressMuted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 7.dp))
            }
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                ProgressMetric("COMPLETED SESSIONS", totalSessions.toString(), Modifier.weight(1f))
                ProgressMetric("AVERAGE SCORE", if (totalSessions == 0) "—" else "${averageScore.toInt()}%", Modifier.weight(1f))
            }
        }
        item {
            ProgressCard {
                Text("Accuracy by CSE area", color = ProgressInk, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                Text("Based on questions you have answered", color = ProgressMuted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 3.dp, bottom = 12.dp))
                categories.forEach { area ->
                    AccuracyBar(area.name, area.attempted, area.correct, area.percentage, area.color)
                }
                if (categories.none { it.attempted > 0 }) {
                    Text("Complete a practice session to start your chart.", color = ProgressMuted, style = MaterialTheme.typography.bodySmall)
                }
            }
        }
        item {
            ProgressCard {
                Text("Areas to strengthen", color = ProgressInk, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                if (weakestCategory == null) {
                    Text("Your weakest category will appear after you complete practice.", color = ProgressMuted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 7.dp))
                } else {
                    Text("Lowest category accuracy: ${weakestCategory.name} (${weakestCategory.percentage.toInt()}%, ${weakestCategory.attempted} questions)", color = ProgressMuted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 7.dp))
                    Button(onClick = { onFocusArea(weakestCategory.code, "ALL") }, modifier = Modifier.fillMaxWidth().padding(top = 8.dp).heightIn(min = 48.dp), colors = ButtonDefaults.buttonColors(containerColor = ProgressNavy)) {
                        Text("Practice ${weakestCategory.name}")
                    }
                }
                if (weakestTopic != null) {
                    Text("Topic to review: ${weakestTopic.name} (${weakestTopic.percentage.toInt()}%, ${weakestTopic.attempted} questions)", color = ProgressMuted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 10.dp))
                    if (weakestTopic.attempted < 5) {
                        Text("Early estimate — answer at least five questions in a topic for a steadier comparison.", color = ProgressMuted, style = MaterialTheme.typography.labelSmall, modifier = Modifier.padding(top = 3.dp))
                    }
                    Button(onClick = { onFocusArea(weakestTopic.categoryCode, weakestTopic.name) }, modifier = Modifier.fillMaxWidth().padding(top = 8.dp).heightIn(min = 48.dp), colors = ButtonDefaults.buttonColors(containerColor = ProgressNavy)) {
                        Text("Practice ${weakestTopic.name}")
                    }
                } else if (topics.isEmpty()) {
                    Text("Topic results will appear after you answer questions with topics assigned.", color = ProgressMuted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 7.dp))
                }
            }
        }
        item {
            ProgressCard {
                Text("Missed questions", color = ProgressInk, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                Text("${missedCount} missed or unanswered question records saved on this phone.", color = ProgressMuted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 5.dp))
                Button(onClick = onReviewMissed, enabled = missedCount > 0, modifier = Modifier.fillMaxWidth().padding(top = 8.dp).heightIn(min = 48.dp), colors = ButtonDefaults.buttonColors(containerColor = ProgressRed)) {
                    Text("Review missed questions")
                }
            }
        }
    }
}

@Composable
fun MissedQuestionsPage(attempts: List<PracticeAttempt>, settings: LocalExamSettings?, onPractice: (List<LocalQuestion>) -> Unit) {
    val missedQuestions = remember(attempts, settings?.progressSnapshotJson) {
        val snapshot = runCatching { JSONObject(settings?.progressSnapshotJson ?: "{}") }.getOrDefault(JSONObject())
        allMissedQuestions(attempts, snapshot)
    }

    Column(Modifier.fillMaxSize().padding(horizontal = 18.dp, vertical = 16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
        Text("Questions to review", color = ProgressInk, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold)
        Text("Revisit questions you missed or left unanswered.", color = ProgressMuted, style = MaterialTheme.typography.bodyMedium)
        Button(onClick = { onPractice(missedQuestions.map { it.localQuestion }) }, enabled = missedQuestions.isNotEmpty(), modifier = Modifier.fillMaxWidth().heightIn(min = 48.dp), shape = RoundedCornerShape(15.dp), colors = ButtonDefaults.buttonColors(containerColor = ProgressRed)) {
            Text("Practice ${missedQuestions.size} missed questions")
        }
        if (missedQuestions.isEmpty()) {
            ProgressCard {
                Text("You’re all caught up", color = ProgressInk, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                Text("Missed and unanswered questions will appear here after a practice session.", color = ProgressMuted, style = MaterialTheme.typography.bodySmall, lineHeight = 19.sp, modifier = Modifier.padding(top = 5.dp))
            }
        } else {
            LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                items(missedQuestions) { missed ->
                    ProgressCard {
                        val question = missed.localQuestion
                        Surface(color = Color(0xFFFFF1F1), shape = RoundedCornerShape(50)) {
                            Text("${question.category}${if (question.topic.isBlank()) "" else "  ·  ${question.topic}"}", color = ProgressRed, style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp))
                        }
                        Text(formattedQuestionPrompt(question.prompt), color = ProgressInk, style = MaterialTheme.typography.titleSmall, lineHeight = 22.sp, fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(top = 10.dp))
                        Text("Your answer  ·  ${missed.selectedAnswer ?: "Unanswered"}", color = ProgressRed, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 9.dp))
                        Text("Correct answer  ·  ${question.correctAnswer}", color = Color(0xFF15803D), style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(top = 4.dp))
                        Text(question.explanation.ifBlank { "No explanation was provided." }, color = ProgressMuted, style = MaterialTheme.typography.bodySmall, lineHeight = 19.sp, modifier = Modifier.padding(top = 8.dp))
                    }
                }
            }
        }
    }
}

@Composable
private fun AccuracyBar(name: String, attempted: Int, correct: Int, percentage: Double, color: Color) {
    Column(Modifier.fillMaxWidth().padding(bottom = 11.dp)) {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Text(name, modifier = Modifier.weight(1f).padding(end = 8.dp), color = ProgressInk, style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.SemiBold)
            Text(if (attempted == 0) "No attempts" else "${percentage.toInt()}% · $correct/$attempted", color = ProgressMuted, style = MaterialTheme.typography.labelSmall, maxLines = 1, softWrap = false)
        }
        LinearProgressIndicator(
            progress = { if (attempted == 0) 0f else (percentage / 100.0).toFloat().coerceIn(0f, 1f) },
            modifier = Modifier.fillMaxWidth().padding(top = 5.dp).height(7.dp),
            color = color,
            trackColor = Color(0xFFE2E8F0),
        )
    }
}

@Composable
private fun ProgressMetric(label: String, value: String, modifier: Modifier = Modifier) {
    ProgressCard(modifier = modifier) {
        Text(label, color = ProgressMuted, style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, letterSpacing = 0.5.sp)
        Text(value, color = ProgressNavy, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold, modifier = Modifier.padding(top = 7.dp))
    }
}

@Composable
private fun ProgressCard(modifier: Modifier = Modifier, content: @Composable androidx.compose.foundation.layout.ColumnScope.() -> Unit) {
    Card(modifier = modifier.fillMaxWidth(), shape = RoundedCornerShape(20.dp), colors = CardDefaults.cardColors(containerColor = Color.White), elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)) {
        Column(Modifier.padding(16.dp), content = content)
    }
}

private fun combineCategoryAccuracy(snapshot: JSONObject, attempts: List<PracticeAttempt>): List<AreaAccuracy> {
    val categoryMap = linkedMapOf<String, AreaAccuracy>()
    val remoteCategories = snapshot.optJSONArray("category_stats") ?: JSONArray()
    for (index in 0 until remoteCategories.length()) {
        val row = remoteCategories.optJSONObject(index) ?: continue
        val code = row.optString("code")
        categoryMap[code] = AreaAccuracy(
            code = code,
            name = row.optString("name", code),
            attempted = row.optInt("attempted_count"),
            correct = row.optInt("correct_count"),
            color = runCatching { Color(android.graphics.Color.parseColor(row.optString("color", "#174A8B"))) }.getOrDefault(ProgressNavy),
        )
    }

    attempts.filter { !it.synced }.forEach { attempt ->
        val rows = runCatching { JSONArray(attempt.categoryBreakdownJson) }.getOrDefault(JSONArray())
        for (index in 0 until rows.length()) {
            val row = rows.optJSONObject(index) ?: continue
            val code = row.optString("code")
            if (code.isBlank()) continue
            val current = categoryMap[code] ?: AreaAccuracy(code, row.optString("name", code), 0, 0, ProgressNavy)
            categoryMap[code] = current.copy(attempted = current.attempted + row.optInt("total"), correct = current.correct + row.optInt("correct"))
        }
    }

    val order = listOf("VERBAL", "ANALYTICAL", "NUMERICAL", "GENERAL")
    return categoryMap.values.sortedBy { order.indexOf(it.code).let { index -> if (index < 0) Int.MAX_VALUE else index } }
}

private fun combineTopicAccuracy(snapshot: JSONObject, attempts: List<PracticeAttempt>): List<TopicAccuracy> {
    val topicMap = linkedMapOf<String, TopicAccuracy>()
    val remoteTopics = snapshot.optJSONArray("topic_stats") ?: JSONArray()
    for (index in 0 until remoteTopics.length()) {
        val row = remoteTopics.optJSONObject(index) ?: continue
        val categoryCode = categoryCodeForName(row.optString("category_name"))
        val name = row.optString("name")
        val key = "$categoryCode|$name"
        topicMap[key] = TopicAccuracy(categoryCode, row.optString("category_name"), name, row.optInt("attempted_count"), row.optInt("correct_count"))
    }

    attempts.filter { !it.synced }.forEach { attempt ->
        val rows = runCatching { JSONArray(attempt.topicBreakdownJson) }.getOrDefault(JSONArray())
        for (index in 0 until rows.length()) {
            val row = rows.optJSONObject(index) ?: continue
            val categoryCode = row.optString("category_code")
            val name = row.optString("name")
            if (categoryCode.isBlank() || name.isBlank()) continue
            val key = "$categoryCode|$name"
            val current = topicMap[key] ?: TopicAccuracy(categoryCode, row.optString("category_name"), name, 0, 0)
            topicMap[key] = current.copy(attempted = current.attempted + row.optInt("total"), correct = current.correct + row.optInt("correct"))
        }
    }

    return topicMap.values.sortedBy { it.percentage }
}

private fun categoryCodeForName(categoryName: String): String = when (categoryName) {
    "Verbal Ability" -> "VERBAL"
    "Analytical Ability" -> "ANALYTICAL"
    "Numerical Ability" -> "NUMERICAL"
    "General Information" -> "GENERAL"
    else -> categoryName.uppercase()
}

private fun calculateStreaks(dates: Set<LocalDate>, today: LocalDate): Pair<Int, Int> {
    val currentStart = when {
        today in dates -> today
        today.minusDays(1) in dates -> today.minusDays(1)
        else -> null
    }
    var current = 0
    if (currentStart != null) {
        var day: LocalDate = currentStart
        while (day in dates) {
            current++
            day = day.minusDays(1)
        }
    }

    var longest = 0
    var run = 0
    var previous: LocalDate? = null
    dates.sorted().forEach { day ->
        run = if (previous != null && previous!!.plusDays(1) == day) run + 1 else 1
        longest = maxOf(longest, run)
        previous = day
    }
    return current to longest
}

private fun parseMissedQuestions(answersJson: String): List<MissedQuestion> {
    val answers = runCatching { JSONArray(answersJson) }.getOrDefault(JSONArray())
    return (0 until answers.length()).mapNotNull { index ->
        val answer = answers.optJSONObject(index) ?: return@mapNotNull null
        if (answer.optBoolean("is_correct") || answer.optString("prompt").isBlank()) return@mapNotNull null
        val choices = answer.optJSONArray("choices") ?: JSONArray()
        fun choice(label: String): String = (0 until choices.length())
            .mapNotNull { choices.optJSONObject(it) }
            .firstOrNull { it.optString("label").equals(label, true) }
            ?.optString("text")
            .orEmpty()

        val localQuestion = LocalQuestion(
            id = answer.optLong("question_id"),
            category = answer.optString("category"),
            topic = (answer.opt("topic") as? String).orEmpty(),
            prompt = answer.optString("prompt"),
            choiceA = choice("A"),
            choiceB = choice("B"),
            choiceC = choice("C"),
            choiceD = choice("D"),
            correctAnswer = answer.optString("correct_answer"),
            explanation = answer.optString("explanation"),
            difficulty = answer.optString("difficulty", "medium"),
            source = answer.optString("source"),
        )
        MissedQuestion(localQuestion, (answer.opt("selected_answer") as? String)?.takeIf(String::isNotBlank), answer.optBoolean("flagged"))
    }
}

private fun allMissedQuestions(attempts: List<PracticeAttempt>, snapshot: JSONObject): List<MissedQuestion> {
    val localQuestions = attempts.flatMap { parseMissedQuestions(it.answersJson) }
    val remoteQuestions = parseMissedQuestions(snapshot.optJSONArray("missed_questions")?.toString() ?: "[]")

    return (localQuestions + remoteQuestions).distinctBy { it.localQuestion.id }
}
