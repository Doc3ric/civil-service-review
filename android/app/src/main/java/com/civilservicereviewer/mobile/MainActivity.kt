package com.civilservicereviewer.mobile

import android.os.Bundle
import android.content.Context
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.core.view.WindowCompat
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.drawscope.withTransform
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import org.json.JSONArray
import org.json.JSONObject
import java.text.DateFormat
import java.util.Date
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

private val Navy = Color(0xFF174A8B)
private val NavyDark = Color(0xFF0F3260)
private val Red = Color(0xFFC92535)
private val Canvas = Color(0xFFF8FAFC)
private val Ink = Color(0xFF1E293B)
private val Muted = Color(0xFF64748B)
private val Line = Color(0xFFE2E8F0)
private val SoftBlue = Color(0xFFEAF2FF)
private val Green = Color(0xFF16835D)

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        WindowCompat.getInsetsController(window, window.decorView).apply {
            isAppearanceLightStatusBars = true
            isAppearanceLightNavigationBars = true
        }
        val dao = ReviewerDatabase.get(this).reviewerDao()
        setContent {
            MaterialTheme(
                colorScheme = lightColorScheme(
                    primary = Navy,
                    onPrimary = Color.White,
                    secondary = Green,
                    background = Canvas,
                    onBackground = Ink,
                    surface = Color.White,
                    onSurface = Ink,
                    error = Red,
                ),
            ) { ReviewerApp(dao) }
        }
    }
}

private enum class Page { HOME, PRACTICE, EXAM, HISTORY, PROGRESS, MISSED, SYNC, QUIZ, RESULT }

@Composable
private fun ReviewerApp(dao: ReviewerDao) {
    val scope = rememberCoroutineScope()
    val context = LocalContext.current
    val questions by dao.observeQuestions().collectAsState(initial = emptyList())
    val attempts by dao.observeAttempts().collectAsState(initial = emptyList())
    val examSettings by dao.observeExamSettings().collectAsState(initial = null)
    var page by remember { mutableStateOf(Page.HOME) }
    var category by remember { mutableStateOf("VERBAL") }
    var topic by remember { mutableStateOf("ALL") }
    var quiz by remember { mutableStateOf(emptyList<LocalQuestion>()) }
    var selected by remember { mutableStateOf(mutableMapOf<Int, String>()) }
    var flagged by remember { mutableStateOf(setOf<Int>()) }
    var index by remember { mutableIntStateOf(0) }
    var secondsLeft by remember { mutableIntStateOf(0) }
    var deadlineAt by remember { mutableLongStateOf(0L) }
    var restoreChecked by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        val preferences = context.getSharedPreferences("reviewer_sync", Context.MODE_PRIVATE)
        val savedServer = preferences.getString("server_url", null)
        val savedToken = preferences.getString("access_token", null)
        if (!savedServer.isNullOrBlank() && !savedToken.isNullOrBlank()) {
            runCatching {
                val uploadResult = MobileSyncClient.uploadPendingAttempts(savedServer, savedToken, dao.getPendingAttempts())
                val snapshot = MobileSyncClient.download(savedServer, savedToken)
                dao.replaceQuestionBank(snapshot.questions, snapshot.settings)
                if (uploadResult.acceptedIds.isNotEmpty()) {
                    dao.markAttemptsSynced(uploadResult.acceptedIds)
                }
            }
        }
    }

    LaunchedEffect(questions, restoreChecked) {
        if (!restoreChecked && questions.isNotEmpty()) {
            restoreChecked = true
            val savedSession = dao.getActivePracticeSession()
            if (savedSession != null) {
                val savedIds = runCatching { JSONArray(savedSession.questionIdsJson) }.getOrDefault(JSONArray())
                val restoredQuiz = (0 until savedIds.length()).mapNotNull { savedIndex ->
                    val questionId = savedIds.optLong(savedIndex)
                    questions.firstOrNull { it.id == questionId }
                }
                if (restoredQuiz.isNotEmpty()) {
                    val savedAnswers = runCatching { JSONObject(savedSession.answersJson) }.getOrDefault(JSONObject())
                    val restoredAnswers = mutableMapOf<Int, String>()
                    val restoredFlags = mutableSetOf<Int>()
                    savedAnswers.keys().forEach { key -> key.toIntOrNull()?.let { restoredAnswers[it] = savedAnswers.optString(key) } }
                    val savedFlags = runCatching { JSONArray(savedSession.flaggedQuestionIndexesJson) }.getOrDefault(JSONArray())
                    for (flagIndex in 0 until savedFlags.length()) restoredFlags += savedFlags.optInt(flagIndex)
                    quiz = restoredQuiz
                    selected = restoredAnswers
                    flagged = restoredFlags
                    index = savedSession.currentQuestionIndex.coerceIn(restoredQuiz.indices)
                    category = savedSession.category
                    deadlineAt = savedSession.deadlineAt
                    secondsLeft = ((deadlineAt - System.currentTimeMillis()) / 1_000L).coerceAtLeast(0).toInt()
                    page = Page.QUIZ
                } else {
                    dao.clearActivePracticeSession()
                }
            }
        }
    }

    LaunchedEffect(page, quiz, selected, flagged, index, deadlineAt) {
        if (page == Page.QUIZ && quiz.isNotEmpty()) {
            val questionIds = JSONArray().apply { quiz.forEach { put(it.id) } }.toString()
            val answers = JSONObject().apply { selected.forEach { (questionIndex, answer) -> put(questionIndex.toString(), answer) } }.toString()
            val flaggedIndexes = JSONArray().apply { flagged.sorted().forEach { put(it) } }.toString()
            dao.saveActivePracticeSession(
                ActivePracticeSession(
                    questionIdsJson = questionIds,
                    answersJson = answers,
                    flaggedQuestionIndexesJson = flaggedIndexes,
                    currentQuestionIndex = index,
                    category = category,
                    deadlineAt = deadlineAt,
                )
            )
        }
    }

    LaunchedEffect(page, secondsLeft) {
        if (page == Page.QUIZ && secondsLeft > 0) {
            delay(1_000)
            secondsLeft -= 1
        } else if (page == Page.QUIZ && secondsLeft == 0) {
            scope.launch {
                val passingPercentage = examSettings?.passingPercentage ?: 80.0
                val allowedSeconds = if (category == "FULL EXAM") (examSettings?.durationMinutes ?: 170) * 60 else quiz.size * 60
                dao.saveAttempt(createPracticeAttempt(
                    title = when (category) {
                        "FULL EXAM" -> "Full practice exam"
                        "QUESTION REVIEW" -> "Missed question review"
                        else -> "Category practice"
                    },
                    category = category,
                    mode = if (category == "FULL EXAM") "full" else "category",
                    quiz = quiz,
                    selectedAnswers = selected,
                    flaggedIndexes = flagged,
                    passingPercentage = passingPercentage,
                    durationSeconds = allowedSeconds - secondsLeft,
                ))
                dao.clearActivePracticeSession()
                page = Page.RESULT
            }
        }
    }

    Surface(modifier = Modifier.fillMaxSize().windowInsetsPadding(WindowInsets.safeDrawing), color = Canvas) {
        Column(Modifier.fillMaxSize()) {
            val showAppHeader = page !in listOf(Page.QUIZ, Page.RESULT)
            if (showAppHeader) {
                TopBar(onSync = { page = Page.SYNC })
            }
            val connectivityManager = remember(context) {
                context.getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
            }
            var hasInternet by remember(connectivityManager) {
                mutableStateOf(hasValidatedInternet(connectivityManager))
            }
            DisposableEffect(connectivityManager) {
                val callback = object : ConnectivityManager.NetworkCallback() {
                    override fun onAvailable(network: Network) {
                        hasInternet = hasValidatedInternet(connectivityManager)
                    }

                    override fun onCapabilitiesChanged(network: Network, capabilities: NetworkCapabilities) {
                        hasInternet = capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED)
                    }

                    override fun onLost(network: Network) {
                        hasInternet = hasValidatedInternet(connectivityManager)
                    }
                }
                connectivityManager.registerDefaultNetworkCallback(callback)
                onDispose { connectivityManager.unregisterNetworkCallback(callback) }
            }
            if (showAppHeader) {
                SyncStatusStrip(
                    hasInternet = hasInternet,
                    syncedAt = examSettings?.syncedAt,
                    pendingAttempts = attempts.count { !it.synced },
                )
            }
            Box(Modifier.weight(1f).fillMaxWidth()) {
                when (page) {
                    Page.HOME -> HomePage(questions, attempts, onPractice = { selectedCategory -> category = selectedCategory; topic = "ALL"; page = Page.PRACTICE }, onExam = { page = Page.EXAM }, onHistory = { page = Page.HISTORY })
                    Page.PRACTICE -> PracticePage(questions, category, topic, onCategory = { category = it; topic = "ALL" }, onTopic = { topic = it }, onStart = { selectedCategory, selectedTopic, count ->
                        quiz = questions.filter { question ->
                            question.category.equals(selectedCategory, true) && (selectedTopic == "ALL" || question.topic == selectedTopic)
                        }.shuffled().take(count)
                        if (quiz.isNotEmpty()) {
                            category = selectedCategory
                            selected = mutableMapOf()
                            flagged = emptySet()
                            index = 0
                            secondsLeft = quiz.size * 60
                            deadlineAt = System.currentTimeMillis() + secondsLeft * 1_000L
                            page = Page.QUIZ
                        }
                    })
                    Page.EXAM -> ExamPage(questions, examSettings) { examQuestions ->
                        quiz = examQuestions
                        if (quiz.isNotEmpty()) {
                            category = "FULL EXAM"
                            selected = mutableMapOf()
                            flagged = emptySet()
                            index = 0
                            secondsLeft = (examSettings?.durationMinutes ?: 170) * 60
                            deadlineAt = System.currentTimeMillis() + secondsLeft * 1_000L
                            page = Page.QUIZ
                        }
                    }
                    Page.HISTORY -> HistoryPage(attempts)
                    Page.PROGRESS -> ProgressPage(
                        attempts = attempts,
                        settings = examSettings,
                        onReviewMissed = { page = Page.MISSED },
                        onFocusArea = { focusCategory, focusTopic ->
                            category = focusCategory
                            topic = focusTopic
                            page = Page.PRACTICE
                        },
                    )
                    Page.MISSED -> MissedQuestionsPage(
                        attempts = attempts,
                        settings = examSettings,
                        onPractice = { missedQuestions ->
                            if (missedQuestions.isNotEmpty()) {
                                quiz = missedQuestions.shuffled()
                                category = "QUESTION REVIEW"
                                selected = mutableMapOf()
                                flagged = emptySet()
                                index = 0
                                secondsLeft = quiz.size * 60
                                deadlineAt = System.currentTimeMillis() + secondsLeft * 1_000L
                                page = Page.QUIZ
                            }
                        },
                    )
                    Page.SYNC -> SyncPage(dao, questions.size, examSettings?.syncedAt)
                    Page.QUIZ -> QuizPage(quiz, selected, flagged, index, secondsLeft, onIndex = { index = it }, onAnswer = { answer -> selected = selected.toMutableMap().apply { put(index, answer) } }, onToggleFlag = {
                        flagged = if (index in flagged) flagged - index else flagged + index
                    }, onSubmit = {
                        scope.launch {
                            val passingPercentage = examSettings?.passingPercentage ?: 80.0
                            val allowedSeconds = if (category == "FULL EXAM") (examSettings?.durationMinutes ?: 170) * 60 else quiz.size * 60
                            dao.saveAttempt(createPracticeAttempt(
                                title = when (category) {
                                    "FULL EXAM" -> "Full practice exam"
                                    "QUESTION REVIEW" -> "Missed question review"
                                    else -> "Category practice"
                                },
                                category = category,
                                mode = if (category == "FULL EXAM") "full" else "category",
                                quiz = quiz,
                                selectedAnswers = selected,
                                flaggedIndexes = flagged,
                                passingPercentage = passingPercentage,
                                durationSeconds = allowedSeconds - secondsLeft,
                            ))
                            dao.clearActivePracticeSession()
                            page = Page.RESULT
                        }
                    })
                    Page.RESULT -> ResultPage(quiz, selected, flagged, examSettings?.passingPercentage ?: 80.0) { page = Page.HOME }
                }
            }
            if (page !in listOf(Page.QUIZ, Page.RESULT)) BottomBar(page, onPage = { page = it })
        }
    }
}

@Composable
private fun TopBar(onSync: () -> Unit) {
    Row(Modifier.fillMaxWidth().background(Color.White).padding(horizontal = 20.dp, vertical = 13.dp), verticalAlignment = Alignment.CenterVertically) {
        Box(Modifier.size(42.dp).background(Red, RoundedCornerShape(14.dp)), contentAlignment = Alignment.Center) { Text("CS", color = Color.White, fontWeight = FontWeight.Black, style = MaterialTheme.typography.titleSmall) }
        Column(Modifier.padding(start = 11.dp).weight(1f)) {
            Text("CIVIL SERVICE", color = NavyDark, fontWeight = FontWeight.Black, style = MaterialTheme.typography.titleSmall, letterSpacing = 0.5.sp)
            Text("REVIEWER 2027", color = Muted, style = MaterialTheme.typography.labelSmall, letterSpacing = 1.sp)
        }
        Surface(onClick = onSync, modifier = Modifier.defaultMinSize(minWidth = 48.dp, minHeight = 48.dp), color = SoftBlue, shape = CircleShape) {
            Row(Modifier.padding(horizontal = 13.dp, vertical = 9.dp), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                Box(Modifier.size(7.dp).background(Green, CircleShape))
                Text("SYNC", color = Navy, style = MaterialTheme.typography.labelMedium, fontWeight = FontWeight.Bold, letterSpacing = 0.5.sp)
            }
        }
    }
}

@Composable
private fun SyncStatusStrip(hasInternet: Boolean, syncedAt: Long?, pendingAttempts: Int) {
    val connectionColor = if (hasInternet) Green else Muted
    val useStackedStatus = LocalDensity.current.fontScale >= 1.3f
    Surface(color = Color.White, tonalElevation = 1.dp) {
        Column(Modifier.fillMaxWidth().padding(horizontal = 18.dp, vertical = 8.dp)) {
            val connectionLabel: @Composable () -> Unit = {
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(7.dp)) {
                    Box(Modifier.size(8.dp).background(connectionColor, CircleShape))
                    Text(if (hasInternet) "Internet available" else "Offline mode", color = connectionColor, style = MaterialTheme.typography.labelMedium, fontWeight = FontWeight.SemiBold)
                }
            }
            val pendingLabel: @Composable () -> Unit = {
                if (pendingAttempts > 0) {
                    Surface(color = Color(0xFFFFF4E5), shape = CircleShape) {
                        Text("$pendingAttempts pending", color = Color(0xFF8A4B08), style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(horizontal = 9.dp, vertical = 6.dp))
                    }
                } else {
                    Text("No pending results", color = Muted, style = MaterialTheme.typography.labelSmall)
                }
            }
            if (useStackedStatus) {
                Column(verticalArrangement = Arrangement.spacedBy(3.dp)) {
                    connectionLabel()
                    pendingLabel()
                }
            } else {
                Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.SpaceBetween) {
                    connectionLabel()
                    pendingLabel()
                }
            }
            val lastSync = syncedAt?.let { DateFormat.getDateTimeInstance(DateFormat.MEDIUM, DateFormat.SHORT).format(Date(it)) } ?: "Not synced yet"
            Text("Last sync: $lastSync", color = Muted, style = MaterialTheme.typography.labelSmall, modifier = Modifier.padding(top = 3.dp))
        }
    }
}

private fun hasValidatedInternet(connectivityManager: ConnectivityManager): Boolean {
    val activeNetwork = connectivityManager.activeNetwork ?: return false
    val capabilities = connectivityManager.getNetworkCapabilities(activeNetwork) ?: return false
    return capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET) &&
        capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED)
}

@Composable
private fun HomePage(questions: List<LocalQuestion>, attempts: List<PracticeAttempt>, onPractice: (String) -> Unit, onExam: () -> Unit, onHistory: () -> Unit) {
    val questionCount = questions.size
    LazyColumn(Modifier.fillMaxSize(), contentPadding = PaddingValues(start = 18.dp, top = 18.dp, end = 18.dp, bottom = 24.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
        item {
            Column(Modifier.fillMaxWidth().background(NavyDark, RoundedCornerShape(26.dp)).padding(22.dp)) {
                Surface(color = Color.White.copy(alpha = .12f), shape = CircleShape) {
                    Text("CSE PROFESSIONAL  ·  2027", color = Color(0xFFD8E7FF), style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, letterSpacing = 0.6.sp, modifier = Modifier.padding(horizontal = 11.dp, vertical = 7.dp))
                }
                Text("Build your confidence, one question at a time.", color = Color.White, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.ExtraBold, lineHeight = 31.sp, modifier = Modifier.padding(top = 16.dp))
                Text("Your question bank and progress stay on this phone, ready for offline study.", color = Color(0xFFDCE7F5), style = MaterialTheme.typography.bodyMedium, lineHeight = 21.sp, modifier = Modifier.padding(top = 8.dp, bottom = 19.dp))
                Button(onClick = onExam, modifier = Modifier.fillMaxWidth().heightIn(min = 52.dp).padding(vertical = 2.dp), shape = RoundedCornerShape(16.dp), colors = ButtonDefaults.buttonColors(containerColor = Red)) {
                    Text("Take Full Practice Exam", fontWeight = FontWeight.Bold)
                }
                Spacer(Modifier.height(9.dp))
                OutlinedButton(onClick = { onPractice("VERBAL") }, modifier = Modifier.fillMaxWidth().heightIn(min = 50.dp).padding(vertical = 2.dp), shape = RoundedCornerShape(16.dp), border = androidx.compose.foundation.BorderStroke(1.dp, Color.White.copy(alpha = .38f)), colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.White)) {
                    Text("Practice a Category", fontWeight = FontWeight.SemiBold)
                }
            }
        }
        item { Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            StatCard("QUESTIONS ON DEVICE", questionCount.toString(), Modifier.weight(1f), "BANK")
            StatCard("SESSIONS COMPLETED", attempts.size.toString(), Modifier.weight(1f), "DONE")
        } }
        item { SectionTitle("Choose a study area", "Practice by category or focus on a topic") }
        items(listOf("VERBAL" to "Verbal Ability", "ANALYTICAL" to "Analytical Ability", "NUMERICAL" to "Numerical Ability", "GENERAL" to "General Information")) { (code, label) ->
            val categoryCount = questions.count { it.category.equals(code, true) }
            Surface(onClick = { onPractice(code) }, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(18.dp), color = Color.White, shadowElevation = 1.dp) {
                Row(Modifier.padding(horizontal = 15.dp, vertical = 14.dp), verticalAlignment = Alignment.CenterVertically) {
                    Box(Modifier.size(46.dp).background(SoftBlue, RoundedCornerShape(15.dp)), contentAlignment = Alignment.Center) { Text(code.take(1), color = Navy, fontWeight = FontWeight.ExtraBold, style = MaterialTheme.typography.titleMedium) }
                    Column(Modifier.padding(start = 13.dp).weight(1f)) {
                        Text(label, color = Ink, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleSmall)
                        Text("$categoryCount questions on this device", color = Muted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 3.dp))
                    }
                    Text("›", color = Navy, style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Light)
                }
            }
        }
        item { Text("View practice history  →", color = Navy, fontWeight = FontWeight.Bold, modifier = Modifier.clickable { onHistory() }.padding(vertical = 8.dp)) }
    }
}

@Composable
private fun PracticePage(
    questions: List<LocalQuestion>,
    category: String,
    topic: String,
    onCategory: (String) -> Unit,
    onTopic: (String) -> Unit,
    onStart: (String, String, Int) -> Unit,
) {
    var count by remember { mutableIntStateOf(10) }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp), verticalArrangement = Arrangement.spacedBy(13.dp)) {
        SectionTitle("Practice by category", "Choose a category and session length")
        listOf("VERBAL", "ANALYTICAL", "NUMERICAL", "GENERAL").forEach { code ->
            val available = questions.count { it.category.equals(code, true) }
            val label = when (code) {
                "VERBAL" -> "Verbal Ability"
                "ANALYTICAL" -> "Analytical Ability"
                "NUMERICAL" -> "Numerical Ability"
                else -> "General Information"
            }
            SelectRow(label, "$available questions available", category == code) { onCategory(code) }
        }
        val categoryTopics = questions.filter { it.category.equals(category, true) }
            .map { it.topic }
            .filter(String::isNotBlank)
            .distinct()
            .sorted()
        if (categoryTopics.isNotEmpty()) {
            Text("Topic", color = Ink, fontWeight = FontWeight.Bold)
            Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(selected = topic == "ALL", onClick = { onTopic("ALL") }, label = { Text("All topics") })
                categoryTopics.forEach { availableTopic ->
                    FilterChip(selected = topic == availableTopic, onClick = { onTopic(availableTopic) }, label = { Text(availableTopic) })
                }
            }
        }
        SectionTitle("Question count", "Available questions in this category")
        val available = questions.count { it.category.equals(category, true) && (topic == "ALL" || it.topic == topic) }
        Row(Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            listOf(10, 20, 30, 50, -1).forEach { n ->
                val label = if (n == -1) "All ($available)" else n.toString()
                FilterChip(selected = count == n, onClick = { count = n }, label = { Text(label) })
            }
        }
        Spacer(Modifier.height(4.dp))
        val sessionCount = if (count == -1) available else minOf(count, available)
        ActionButton("Start Practice", Red, enabled = available > 0, onClick = { onStart(category, topic, sessionCount) })
        if (questions.isEmpty()) EmptyBankNotice()
    }
}

@Composable
private fun ExamPage(questions: List<LocalQuestion>, settings: LocalExamSettings?, onStart: (List<LocalQuestion>) -> Unit) {
    val questionCount = questions.size
    val blueprintTotal = settings?.totalQuestions ?: 150
    val preview = remember(questions, settings) { chooseBlueprintQuestions(questions, settings) }
    val allocationRows = remember(settings) {
        val allocations = runCatching { JSONArray(settings?.categoryAllocationsJson ?: "[]") }.getOrDefault(JSONArray())
        (0 until allocations.length()).mapNotNull { allocations.optJSONObject(it) }
    }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
        SectionTitle("Full practice exam", settings?.title ?: "Civil Service Professional blueprint")
        CardBlock {
            Text("$blueprintTotal-item examination", color = Navy, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold)
            Text("${settings?.durationMinutes ?: 170} minutes  ·  Passing target ${settings?.passingPercentage?.toInt() ?: 80}%", color = Muted, modifier = Modifier.padding(top = 7.dp))
            HorizontalDivider(Modifier.padding(vertical = 14.dp), color = Line)
            Text("Questions stored on this phone: $questionCount", color = Ink, fontWeight = FontWeight.SemiBold)
            Text("Questions selected by this blueprint: ${preview.size}", color = Muted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 5.dp))
            allocationRows.forEach { allocation ->
                Text("${allocation.optString("name")}: ${allocation.optInt("question_count")} items", color = Ink, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 6.dp))
            }
        }
        Text("Sync the app while the web server is available. Your saved questions and blueprint stay on this phone for offline practice.", color = Muted, style = MaterialTheme.typography.bodyMedium)
        if (preview.size < blueprintTotal) {
            Text("Your local bank can build only ${preview.size} of $blueprintTotal blueprint questions. Add or sync questions for the missing category allocations before starting a full exam.", color = Red, style = MaterialTheme.typography.bodySmall)
        }
        Spacer(Modifier.height(4.dp))
        ActionButton("Start Full Exam", Red, enabled = preview.size == blueprintTotal, onClick = { onStart(preview) })
        if (questionCount == 0) EmptyBankNotice()
    }
}

private fun chooseBlueprintQuestions(questions: List<LocalQuestion>, settings: LocalExamSettings?): List<LocalQuestion> {
    if (questions.isEmpty()) return emptyList()
    val total = settings?.totalQuestions ?: 150
    val allocations = runCatching { JSONArray(settings?.categoryAllocationsJson ?: "[]") }.getOrDefault(JSONArray())
    if (allocations.length() == 0) return questions.shuffled().take(total)

    val selected = linkedMapOf<Long, LocalQuestion>()
    for (allocationIndex in 0 until allocations.length()) {
        val allocation = allocations.optJSONObject(allocationIndex) ?: continue
        val code = allocation.optString("code")
        val categoryQuestions = questions.filter { it.category.equals(code, true) }
        val categoryTarget = allocation.optInt("question_count").coerceAtLeast(0)
        val topicAllocations = allocation.optJSONArray("topics") ?: JSONArray()

        for (topicIndex in 0 until topicAllocations.length()) {
            val topicAllocation = topicAllocations.optJSONObject(topicIndex) ?: continue
            val topicName = topicAllocation.optString("name")
            val topicTarget = topicAllocation.optInt("question_count").coerceAtLeast(0)
            categoryQuestions
                .filter { it.topic.equals(topicName, true) && it.id !in selected }
                .shuffled()
                .take(topicTarget)
                .forEach { selected[it.id] = it }
        }

        if (topicAllocations.length() == 0) {
            categoryQuestions
                .filter { it.id !in selected }
                .shuffled()
                .take(categoryTarget)
                .forEach { selected[it.id] = it }
        }
    }

    return selected.values.shuffled().take(total)
}

@Composable
private fun SyncPage(dao: ReviewerDao, questionCount: Int, syncedAt: Long?) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val preferences = remember(context) { context.getSharedPreferences("reviewer_sync", Context.MODE_PRIVATE) }
    var serverUrl by remember { mutableStateOf(preferences.getString("server_url", "") ?: "") }
    var email by remember { mutableStateOf(preferences.getString("email", "") ?: "") }
    var password by remember { mutableStateOf("") }
    var message by remember {
        mutableStateOf(if (questionCount > 0) "$questionCount questions are stored on this device." else "Sign in to your web system to download your question bank.")
    }
    var messageIsError by remember { mutableStateOf(false) }
    var syncing by remember { mutableStateOf(false) }
    val reusableSession = preferences.getString("access_token", null) != null &&
        preferences.getString("server_url", null) == serverUrl.trim().trimEnd('/') &&
        preferences.getString("email", null) == email.trim()

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp), verticalArrangement = Arrangement.spacedBy(13.dp)) {
        SectionTitle("Sync your study data", "Download questions and the active blueprint; upload completed practice results")
        OutlinedTextField(
            value = serverUrl,
            onValueChange = { serverUrl = it },
            modifier = Modifier.fillMaxWidth(),
            label = { Text("Web server address") },
            placeholder = { Text("http://192.168.1.20:8000") },
            singleLine = true,
        )
        Text("On a real phone, use your laptop’s Wi-Fi IP address—not localhost. The phone and laptop must be on the same Wi-Fi.", color = Muted, style = MaterialTheme.typography.bodySmall)
        OutlinedTextField(value = email, onValueChange = { email = it }, modifier = Modifier.fillMaxWidth(), label = { Text("Web account email") }, singleLine = true)
        OutlinedTextField(value = password, onValueChange = { password = it }, modifier = Modifier.fillMaxWidth(), label = { Text("Password (needed for first sync or expired session)") }, visualTransformation = PasswordVisualTransformation(), singleLine = true)
        CardBlock {
            Text(if (messageIsError) "Sync needs attention" else "On-device question bank", color = if (messageIsError) Red else Navy, fontWeight = FontWeight.Bold)
            Text(message, color = Muted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 6.dp))
            if (syncedAt != null) {
                Text("Last sync: ${DateFormat.getDateTimeInstance().format(Date(syncedAt))}", color = Muted, style = MaterialTheme.typography.labelSmall, modifier = Modifier.padding(top = 7.dp))
            }
        }
        Text("Sign-in creates a revocable mobile session. Use a trusted network for local HTTP, or use HTTPS when your web system is hosted.", color = Muted, style = MaterialTheme.typography.bodySmall)
        Spacer(Modifier.height(4.dp))
        ActionButton(
            if (syncing) "Syncing…" else "Sync Questions & Progress",
            Red,
            enabled = !syncing && serverUrl.isNotBlank() && email.isNotBlank() && (password.isNotBlank() || reusableSession),
            onClick = {
                scope.launch {
                    syncing = true
                    messageIsError = false
                    message = "Connecting to your web system…"
                    try {
                        val normalizedUrl = serverUrl.trim().trimEnd('/')
                        val savedUrl = preferences.getString("server_url", null)
                        val savedToken = preferences.getString("access_token", null)
                            .takeIf { savedUrl == normalizedUrl && preferences.getString("email", null) == email.trim() }
                        val token = savedToken ?: MobileSyncClient.signIn(normalizedUrl, email, password, android.os.Build.MODEL)
                        val pendingAttempts = dao.getPendingAttempts()
                        val attemptSync = MobileSyncClient.uploadPendingAttempts(normalizedUrl, token, pendingAttempts)
                        val snapshot = MobileSyncClient.download(normalizedUrl, token)
                        dao.replaceQuestionBank(snapshot.questions, snapshot.settings)
                        if (attemptSync.acceptedIds.isNotEmpty()) {
                            dao.markAttemptsSynced(attemptSync.acceptedIds)
                        }
                        preferences.edit()
                            .putString("server_url", normalizedUrl)
                            .putString("email", email.trim())
                            .putString("access_token", token)
                            .apply()
                        password = ""
                        message = buildString {
                            append("Synced ${snapshot.questions.size} published questions and ${attemptSync.acceptedIds.size} progress sessions.")
                            if (attemptSync.rejectedCount > 0) {
                                append(" ${attemptSync.rejectedCount} session(s) will retry: ${attemptSync.firstError ?: "some questions could not be matched"}")
                            } else {
                                append(" Your questions and progress are available offline.")
                            }
                        }
                        messageIsError = attemptSync.rejectedCount > 0
                    } catch (exception: Exception) {
                        val errorMessage = exception.message ?: "Could not sync. Check the address and connection, then try again."
                        if (errorMessage.contains("session expired", ignoreCase = true)) {
                            preferences.edit().remove("access_token").apply()
                        }
                        message = errorMessage
                        messageIsError = true
                    } finally {
                        syncing = false
                    }
                }
            },
        )
        if (preferences.getString("access_token", null) != null) {
            OutlinedButton(
                onClick = {
                    scope.launch {
                        val normalizedUrl = serverUrl.trim().trimEnd('/')
                        val savedToken = preferences.getString("access_token", null)
                        runCatching {
                            if (savedToken != null) MobileSyncClient.revoke(normalizedUrl, savedToken)
                        }
                        preferences.edit().remove("access_token").apply()
                        message = "This phone has been signed out. Your saved questions remain available offline."
                        messageIsError = false
                    }
                },
                enabled = !syncing,
                modifier = Modifier.fillMaxWidth(),
            ) { Text("Sign out this device") }
        }
    }
}

@Composable
private fun QuizPage(
    quiz: List<LocalQuestion>,
    selected: Map<Int, String>,
    flagged: Set<Int>,
    index: Int,
    secondsLeft: Int,
    onIndex: (Int) -> Unit,
    onAnswer: (String) -> Unit,
    onToggleFlag: () -> Unit,
    onSubmit: () -> Unit,
) {
    if (quiz.isEmpty()) return
    val question = quiz[index.coerceIn(quiz.indices)]
    val choices = listOf("A" to question.choiceA, "B" to question.choiceB, "C" to question.choiceC, "D" to question.choiceD)
    var showSubmitConfirmation by remember { mutableStateOf(false) }
    Column(Modifier.fillMaxSize().padding(horizontal = 18.dp, vertical = 14.dp)) {
        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.SpaceBetween) {
            Column {
                Text("QUESTION ${index + 1} OF ${quiz.size}", color = Navy, style = MaterialTheme.typography.labelLarge, fontWeight = FontWeight.Bold, letterSpacing = 0.6.sp)
            }
            Surface(color = Color(0xFFFFF1F1), shape = CircleShape) {
                Text("%02d:%02d".format(secondsLeft / 60, secondsLeft % 60), color = Red, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.bodyMedium, modifier = Modifier.padding(horizontal = 13.dp, vertical = 8.dp))
            }
        }
        LinearProgressIndicator(progress = { (index + 1f) / quiz.size }, modifier = Modifier.fillMaxWidth().padding(top = 10.dp).height(6.dp), color = Red, trackColor = Line)
        Row(Modifier.fillMaxWidth().padding(top = 7.dp, bottom = 2.dp).horizontalScroll(rememberScrollState()), horizontalArrangement = Arrangement.spacedBy(6.dp)) {
            quiz.indices.forEach { questionIndex ->
                FilterChip(
                    selected = questionIndex == index,
                    onClick = { onIndex(questionIndex) },
                    label = { Text(if (questionIndex in flagged) "${questionIndex + 1} !" else "${questionIndex + 1}", style = MaterialTheme.typography.labelMedium) },
                )
            }
        }
        LazyColumn(Modifier.weight(1f).fillMaxWidth().padding(top = 5.dp, bottom = 6.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            item {
                CardBlock {
                    Text(question.category + if (question.topic.isNotBlank()) "  ·  ${question.topic}" else "", color = Navy, style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, lineHeight = 16.sp, letterSpacing = 0.25.sp)
                    Text(formattedQuestionPrompt(question.prompt), color = Ink, style = MaterialTheme.typography.titleMedium, lineHeight = 24.sp, fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(top = 11.dp, bottom = 2.dp))
                }
            }
            items(choices.filter { it.second.isNotBlank() }) { (letter, value) ->
                SelectRow("$letter.  $value", "", selected[index] == letter) { onAnswer(letter) }
            }
        }
        QuizFlagAction(isFlagged = index in flagged, onClick = onToggleFlag)
        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            OutlinedButton(onClick = { onIndex((index - 1).coerceAtLeast(0)) }, enabled = index > 0, modifier = Modifier.weight(1f).heightIn(min = 48.dp), shape = RoundedCornerShape(15.dp)) { Text("Previous") }
            if (index < quiz.lastIndex) Button(onClick = { onIndex(index + 1) }, modifier = Modifier.weight(1f).heightIn(min = 48.dp), shape = RoundedCornerShape(15.dp), colors = ButtonDefaults.buttonColors(containerColor = Navy)) { Text("Next", fontWeight = FontWeight.Bold) }
            else Button(onClick = { showSubmitConfirmation = true }, modifier = Modifier.weight(1f).heightIn(min = 48.dp), shape = RoundedCornerShape(15.dp), colors = ButtonDefaults.buttonColors(containerColor = Red)) { Text("Submit", fontWeight = FontWeight.Bold) }
        }
    }

    if (showSubmitConfirmation) {
        val unansweredCount = quiz.indices.count { it !in selected }
        AlertDialog(
            onDismissRequest = { showSubmitConfirmation = false },
            title = { Text("Submit this practice session?") },
            text = { Text(if (unansweredCount == 0) "You answered every question." else "$unansweredCount question(s) are unanswered.") },
            confirmButton = { TextButton(onClick = onSubmit) { Text("Submit", color = Red) } },
            dismissButton = { TextButton(onClick = { showSubmitConfirmation = false }) { Text("Keep reviewing") } },
        )
    }
}

@Composable
private fun QuizFlagAction(isFlagged: Boolean, onClick: () -> Unit) {
    val flagColor = Color(0xFF9A5B00)
    val containerColor = if (isFlagged) Color(0xFFFFF6E5) else Color.White
    val borderColor = if (isFlagged) Color(0xFFF0D39A) else Line

    Surface(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth().heightIn(min = 54.dp),
        shape = RoundedCornerShape(16.dp),
        color = containerColor,
        contentColor = Ink,
        border = androidx.compose.foundation.BorderStroke(1.dp, borderColor),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(horizontal = 14.dp, vertical = 9.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            Canvas(modifier = Modifier.size(23.dp)) {
                val scaleFactor = size.minDimension / 24f
                withTransform({ scale(scaleFactor, scaleFactor, pivot = Offset.Zero) }) {
                    val flagShape = Path().apply {
                        moveTo(7f, 3f)
                        lineTo(19f, 5f)
                        lineTo(15f, 10f)
                        lineTo(19f, 15f)
                        lineTo(7f, 13f)
                        close()
                    }
                    drawLine(flagColor, Offset(6f, 3f), Offset(6f, 21f), strokeWidth = 2f, cap = StrokeCap.Round)
                    drawPath(flagShape, flagColor, style = Stroke(width = if (isFlagged) 2.2f else 1.8f, cap = StrokeCap.Round))
                }
            }
            Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(1.dp)) {
                Text(
                    if (isFlagged) "Marked for review" else "Flag for review",
                    color = if (isFlagged) flagColor else Ink,
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    if (isFlagged) "Tap to remove this flag" else "Come back to this question later",
                    color = Muted,
                    style = MaterialTheme.typography.labelSmall,
                )
            }
            if (isFlagged) {
                Surface(color = Color(0xFFFFE9BE), shape = CircleShape) {
                    Text(
                        "REVIEW",
                        modifier = Modifier.padding(horizontal = 9.dp, vertical = 5.dp),
                        color = flagColor,
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold,
                        letterSpacing = 0.3.sp,
                    )
                }
            }
        }
    }
}

@Composable
private fun ResultPage(quiz: List<LocalQuestion>, selected: Map<Int, String>, flagged: Set<Int>, passingPercentage: Double, onDone: () -> Unit) {
    val correct = quiz.indices.count { selected[it] == quiz[it].correctAnswer }
    val total = quiz.size
    val score = if (total == 0) 0.0 else correct * 100.0 / total
    val breakdown = quiz.indices.groupBy { questionIndex ->
        val question = quiz[questionIndex]
        question.category.trim() to question.topic.trim()
    }
    Column(Modifier.fillMaxSize().padding(horizontal = 18.dp, vertical = 12.dp)) {
        CardBlock(modifier = Modifier.padding(bottom = 13.dp)) {
            Text("PRACTICE COMPLETE", color = Navy, style = MaterialTheme.typography.labelLarge, fontWeight = FontWeight.Bold, letterSpacing = 0.8.sp)
            Text("${score.toInt()}%", color = NavyDark, style = MaterialTheme.typography.displayMedium, fontWeight = FontWeight.Black, modifier = Modifier.padding(top = 2.dp))
            Text("$correct correct out of $total", color = Ink, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
            Text("Passing target ${passingPercentage.toInt()}%${if (score >= passingPercentage) "  ·  Passed" else ""}", color = if (score >= passingPercentage) Green else Muted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 4.dp))
        }
        LazyColumn(
            modifier = Modifier.weight(1f),
            contentPadding = PaddingValues(bottom = 14.dp),
            verticalArrangement = Arrangement.spacedBy(9.dp),
        ) {
            item { Text("Score by category and topic", color = Ink, fontWeight = FontWeight.ExtraBold, style = MaterialTheme.typography.titleMedium) }
            items(breakdown.toList()) { (categoryAndTopic, questionIndexes) ->
                val groupCorrect = questionIndexes.count { selected[it] == quiz[it].correctAnswer }
                Row(
                    modifier = Modifier.fillMaxWidth().background(Color.White, RoundedCornerShape(16.dp)).padding(14.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                ) {
                    Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(3.dp)) {
                        Text(categoryAndTopic.first, color = Navy, style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, letterSpacing = 0.4.sp)
                        if (categoryAndTopic.second.isNotBlank()) {
                            Text(categoryAndTopic.second, color = Ink, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold, lineHeight = 20.sp)
                        }
                    }
                    Surface(color = SoftBlue, shape = RoundedCornerShape(12.dp)) {
                        Text(
                            "$groupCorrect / ${questionIndexes.size}",
                            modifier = Modifier.padding(horizontal = 10.dp, vertical = 7.dp),
                            color = Navy,
                            style = MaterialTheme.typography.labelLarge,
                            fontWeight = FontWeight.Bold,
                            maxLines = 1,
                            softWrap = false,
                        )
                    }
                }
            }
            item { Text("Answer review", color = Ink, fontWeight = FontWeight.ExtraBold, style = MaterialTheme.typography.titleLarge, modifier = Modifier.padding(top = 8.dp, bottom = 2.dp)) }
            items(quiz.indices.toList()) { questionIndex ->
                val item = quiz[questionIndex]
                val answer = selected[questionIndex]
                val correctChoice = item.correctAnswer
                val choiceText: (String?) -> String = { choice ->
                    when (choice) {
                        "A" -> item.choiceA
                        "B" -> item.choiceB
                        "C" -> item.choiceC
                        "D" -> item.choiceD
                        else -> "Unanswered"
                    }
                }
                val isCorrect = answer == correctChoice
                val resultColor = if (isCorrect) Green else Red
                val resultSurface = if (isCorrect) Color(0xFFEAF7F0) else Color(0xFFFFF1F1)
                CardBlock {
                    Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.SpaceBetween) {
                        Text("QUESTION ${questionIndex + 1}", color = Muted, style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, letterSpacing = 0.6.sp)
                        Surface(color = resultSurface, shape = CircleShape) {
                            Text(
                                when {
                                    isCorrect -> "CORRECT"
                                    answer == null -> "UNANSWERED"
                                    else -> "REVIEW"
                                },
                                color = resultColor,
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                            )
                        }
                    }
                    Text(formattedQuestionPrompt(item.prompt), color = Ink, style = MaterialTheme.typography.titleSmall, lineHeight = 23.sp, fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(top = 11.dp))
                    HorizontalDivider(Modifier.padding(vertical = 11.dp), color = Line)
                    Text("YOUR ANSWER", color = Muted, style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, letterSpacing = 0.5.sp)
                    Text("${answer ?: "Unanswered"}${if (answer == null) "" else "  ·  ${choiceText(answer)}"}", color = resultColor, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold, lineHeight = 21.sp, modifier = Modifier.padding(top = 3.dp))
                    Text("CORRECT ANSWER", color = Muted, style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, letterSpacing = 0.5.sp, modifier = Modifier.padding(top = 10.dp))
                    Text("$correctChoice  ·  ${choiceText(correctChoice)}", color = Green, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold, lineHeight = 21.sp, modifier = Modifier.padding(top = 3.dp))
                    if (questionIndex in flagged) Text("Flagged for review", color = Navy, style = MaterialTheme.typography.labelSmall, modifier = Modifier.padding(top = 8.dp))
                    HorizontalDivider(Modifier.padding(vertical = 11.dp), color = Line)
                    Text("EXPLANATION", color = Muted, style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold, letterSpacing = 0.5.sp)
                    Text(item.explanation.ifBlank { "No explanation was provided for this question." }, color = Ink, style = MaterialTheme.typography.bodySmall, lineHeight = 20.sp, modifier = Modifier.padding(top = 4.dp))
                }
            }
        }
        ActionButton("Back to Dashboard", Red, onClick = onDone)
    }
}

@Composable
private fun HistoryPage(attempts: List<PracticeAttempt>) {
    Column(Modifier.fillMaxSize().padding(18.dp)) {
        SectionTitle("Practice history", "Saved on this phone")
        if (attempts.isEmpty()) CardBlock(Modifier.padding(top = 16.dp)) { Text("Your study sessions will appear here", color = Ink, fontWeight = FontWeight.Bold); Text("Finish a practice session to start building your history.", color = Muted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 5.dp)) }
        else LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.padding(top = 14.dp)) {
            items(attempts) { attempt -> CardBlock {
                Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top, horizontalArrangement = Arrangement.SpaceBetween) {
                    Column(Modifier.weight(1f).padding(end = 10.dp)) {
                        Text(attempt.title, color = Ink, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleSmall)
                        Text("${attempt.category}  ·  ${DateFormat.getDateTimeInstance().format(Date(attempt.completedAt))}", color = Muted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 5.dp))
                    }
                    Text("${attempt.scorePercentage.toInt()}%", color = Navy, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold)
                }
                Text("${attempt.correctCount}/${attempt.questionCount} correct", color = Muted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 8.dp))
                Text(if (attempt.synced) "●  Synced with web" else "●  Saved offline · waiting to sync", color = if (attempt.synced) Green else Navy, style = MaterialTheme.typography.labelSmall, modifier = Modifier.padding(top = 7.dp))
            } }
        }
    }
}

@Composable
private fun BottomBar(page: Page, onPage: (Page) -> Unit) {
    NavigationBar(containerColor = Color.White, tonalElevation = 4.dp, windowInsets = WindowInsets.navigationBars) {
        listOf(Page.HOME to "Home", Page.PRACTICE to "Practice", Page.EXAM to "Full Exam", Page.PROGRESS to "Progress", Page.HISTORY to "History").forEach { (target, label) ->
            val isSelected = page == target || (page == Page.MISSED && target == Page.PROGRESS)
            NavigationBarItem(
                selected = isSelected,
                onClick = { onPage(target) },
                icon = { ReviewerNavIcon(target, label, isSelected) },
                label = { Text(label, style = MaterialTheme.typography.labelSmall, fontWeight = if (isSelected) FontWeight.SemiBold else FontWeight.Medium, maxLines = 1) },
                colors = NavigationBarItemDefaults.colors(selectedIconColor = Navy, selectedTextColor = Navy, unselectedIconColor = Muted, unselectedTextColor = Muted, indicatorColor = SoftBlue),
            )
        }
    }
}

@Composable
private fun ReviewerNavIcon(page: Page, label: String, selected: Boolean) {
    val iconColor = if (selected) Navy else Muted
    Canvas(
        modifier = Modifier.size(23.dp).clearAndSetSemantics { },
        onDraw = {
            val factor = size.minDimension / 24f
            val line = if (selected) 2.0f else 1.7f
            val stroke = Stroke(width = line, cap = StrokeCap.Round)
            val point = { x: Float, y: Float -> Offset(x, y) }

            withTransform({ scale(factor, factor, pivot = Offset.Zero) }) {
                when (page) {
                    Page.HOME -> {
                        val house = Path().apply {
                            moveTo(3.5f, 10.7f)
                            lineTo(12f, 3.8f)
                            lineTo(20.5f, 10.7f)
                            moveTo(5.5f, 9.5f)
                            lineTo(5.5f, 19.5f)
                            quadraticTo(5.5f, 20.5f, 6.5f, 20.5f)
                            lineTo(17.5f, 20.5f)
                            quadraticTo(18.5f, 20.5f, 18.5f, 19.5f)
                            lineTo(18.5f, 9.5f)
                            moveTo(10f, 20f)
                            lineTo(10f, 14.5f)
                            quadraticTo(10f, 13.5f, 11f, 13.5f)
                            lineTo(13f, 13.5f)
                            quadraticTo(14f, 13.5f, 14f, 14.5f)
                            lineTo(14f, 20f)
                        }
                        drawPath(house, iconColor, style = stroke)
                    }
                    Page.PRACTICE -> {
                        drawCircle(iconColor, radius = 9.2f, center = point(12f, 12f), style = stroke)
                        val check = Path().apply { moveTo(7.8f, 12.1f); lineTo(10.5f, 14.8f); lineTo(16.5f, 9.4f) }
                        drawPath(check, iconColor, style = stroke)
                    }
                    Page.EXAM -> {
                        drawRoundRect(iconColor, topLeft = point(5.3f, 4.5f), size = androidx.compose.ui.geometry.Size(13.4f, 16f), cornerRadius = androidx.compose.ui.geometry.CornerRadius(2.2f), style = stroke)
                        drawLine(iconColor, point(9f, 9.2f), point(15f, 9.2f), strokeWidth = line, cap = StrokeCap.Round)
                        drawLine(iconColor, point(9f, 12.5f), point(15f, 12.5f), strokeWidth = line, cap = StrokeCap.Round)
                        drawLine(iconColor, point(9f, 15.8f), point(13.2f, 15.8f), strokeWidth = line, cap = StrokeCap.Round)
                    }
                    Page.PROGRESS -> {
                        drawLine(iconColor, point(4.5f, 19.5f), point(20f, 19.5f), strokeWidth = line, cap = StrokeCap.Round)
                        drawLine(iconColor, point(5.5f, 18.5f), point(5.5f, 13.5f), strokeWidth = line + 1.2f, cap = StrokeCap.Round)
                        drawLine(iconColor, point(11f, 18.5f), point(11f, 8.5f), strokeWidth = line + 1.2f, cap = StrokeCap.Round)
                        drawLine(iconColor, point(16.5f, 18.5f), point(16.5f, 5f), strokeWidth = line + 1.2f, cap = StrokeCap.Round)
                    }
                    Page.HISTORY -> {
                        drawCircle(iconColor, radius = 9.2f, center = point(12f, 12f), style = stroke)
                        drawLine(iconColor, point(12f, 6.8f), point(12f, 12f), strokeWidth = line, cap = StrokeCap.Round)
                        drawLine(iconColor, point(12f, 12f), point(16f, 14.3f), strokeWidth = line, cap = StrokeCap.Round)
                    }
                    else -> Unit
                }
            }
        },
    )
}

@Composable
private fun SectionTitle(title: String, subtitle: String) {
    Column(Modifier.padding(bottom = 2.dp)) { Text(title, color = Ink, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold); Text(subtitle, color = Muted, style = MaterialTheme.typography.bodySmall, lineHeight = 19.sp, modifier = Modifier.padding(top = 4.dp)) }
}

@Composable
private fun StatCard(label: String, value: String, modifier: Modifier = Modifier, eyebrow: String) {
    Card(modifier = modifier, shape = RoundedCornerShape(19.dp), colors = CardDefaults.cardColors(containerColor = Color.White), elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)) {
        Column(Modifier.padding(15.dp)) {
            Text(eyebrow, color = Navy, style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.ExtraBold, letterSpacing = 0.7.sp)
            Text(value, color = NavyDark, style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.ExtraBold, modifier = Modifier.padding(top = 5.dp))
            Text(label, color = Muted, style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.SemiBold, lineHeight = 15.sp, modifier = Modifier.padding(top = 1.dp))
        }
    }
}

@Composable
private fun CardBlock(modifier: Modifier = Modifier, content: @Composable ColumnScope.() -> Unit) {
    Card(modifier = modifier.fillMaxWidth(), shape = RoundedCornerShape(20.dp), colors = CardDefaults.cardColors(containerColor = Color.White), elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)) {
        Column(Modifier.padding(18.dp), content = content)
    }
}

@Composable
private fun SelectRow(title: String, subtitle: String, selected: Boolean, onClick: () -> Unit) {
    Surface(onClick = onClick, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(17.dp), color = if (selected) SoftBlue else Color.White, shadowElevation = if (selected) 0.dp else 1.dp) {
        Row(Modifier.padding(horizontal = 14.dp, vertical = 10.dp), verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) { Text(title, color = Ink, style = MaterialTheme.typography.bodyMedium, lineHeight = 20.sp, fontWeight = FontWeight.SemiBold); if (subtitle.isNotBlank()) Text(subtitle, color = Muted, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 3.dp)) }
            RadioButton(selected, onClick = onClick, colors = RadioButtonDefaults.colors(selectedColor = Navy))
        }
    }
}

@Composable
private fun ActionButton(label: String, color: Color, textColor: Color = Color.White, enabled: Boolean = true, onClick: () -> Unit) {
    Button(onClick = onClick, enabled = enabled, modifier = Modifier.fillMaxWidth().heightIn(min = 52.dp).padding(vertical = 2.dp), shape = RoundedCornerShape(16.dp), colors = ButtonDefaults.buttonColors(containerColor = color, contentColor = textColor)) { Text(label, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleSmall, lineHeight = 21.sp, modifier = Modifier.padding(vertical = 5.dp)) }
}

@Composable
private fun EmptyBankNotice() {
    CardBlock {
        Row(verticalAlignment = Alignment.Top) {
            Box(Modifier.size(38.dp).background(Color(0xFFFFF1F1), RoundedCornerShape(13.dp)), contentAlignment = Alignment.Center) { Text("!", color = Red, fontWeight = FontWeight.ExtraBold, style = MaterialTheme.typography.titleMedium) }
            Column(Modifier.padding(start = 11.dp).weight(1f)) {
                Text("Your question bank is empty", color = Ink, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleSmall)
                Text("Sync your trusted questions from the web system once. They’ll stay on this phone for offline study.", color = Muted, style = MaterialTheme.typography.bodySmall, lineHeight = 19.sp, modifier = Modifier.padding(top = 4.dp))
            }
        }
    }
}
