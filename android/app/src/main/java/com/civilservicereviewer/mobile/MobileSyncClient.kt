package com.civilservicereviewer.mobile

import org.json.JSONObject
import org.json.JSONArray
import java.io.IOException
import java.net.HttpURLConnection
import java.net.URL
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

data class MobileBankSnapshot(
    val questions: List<LocalQuestion>,
    val settings: LocalExamSettings,
)

data class MobileAttemptSyncResult(
    val acceptedIds: List<String>,
    val rejectedCount: Int,
    val firstError: String?,
)

object MobileSyncClient {
    suspend fun signIn(
        serverUrl: String,
        email: String,
        password: String,
        deviceName: String,
    ): String = withContext(Dispatchers.IO) {
        val normalizedUrl = validateServerUrl(serverUrl)
        val login = JSONObject()
            .put("email", email.trim())
            .put("password", password)
            .put("device_name", deviceName)
        requestJson("POST", "$normalizedUrl/api/mobile/session", body = login)
            .getString("token")
    }

    suspend fun download(serverUrl: String, token: String): MobileBankSnapshot = withContext(Dispatchers.IO) {
        val normalizedUrl = validateServerUrl(serverUrl)
        val payload = requestJson("GET", "$normalizedUrl/api/mobile/question-bank", token = token)
        val blueprint = payload.getJSONObject("blueprint")
        val questionPayload = payload.getJSONArray("questions")
        val questions = (0 until questionPayload.length()).map { index ->
            val item = questionPayload.getJSONObject(index)
            val choices = item.getJSONArray("choices")
            fun choiceText(label: String): String {
                return (0 until choices.length())
                    .map { choices.getJSONObject(it) }
                    .firstOrNull { it.optString("label").equals(label, ignoreCase = true) }
                    ?.optString("text")
                    .orEmpty()
            }

            LocalQuestion(
                id = item.getLong("id"),
                category = item.getString("category"),
                topic = item.optString("topic"),
                prompt = item.getString("prompt"),
                choiceA = choiceText("A"),
                choiceB = choiceText("B"),
                choiceC = choiceText("C"),
                choiceD = choiceText("D"),
                correctAnswer = item.getString("correct_answer"),
                explanation = item.optString("explanation"),
                difficulty = item.optString("difficulty", "medium"),
                source = item.optString("source"),
            )
        }

        val allocations = blueprint.optJSONArray("categories")?.toString() ?: "[]"
        val settings = LocalExamSettings(
            title = blueprint.optString("title", "Civil Service Professional Level"),
            totalQuestions = blueprint.optInt("total_questions", 150),
            durationMinutes = blueprint.optInt("duration_minutes", 170),
            passingPercentage = blueprint.optDouble("passing_percentage", 80.0),
            categoryAllocationsJson = allocations,
            revision = payload.optString("revision"),
            syncedAt = System.currentTimeMillis(),
            progressSnapshotJson = payload.optJSONObject("progress")?.toString() ?: "{}",
        )

        MobileBankSnapshot(questions = questions, settings = settings)
    }

    suspend fun uploadPendingAttempts(
        serverUrl: String,
        token: String,
        attempts: List<PracticeAttempt>,
    ): MobileAttemptSyncResult = withContext(Dispatchers.IO) {
        if (attempts.isEmpty()) {
            return@withContext MobileAttemptSyncResult(emptyList(), 0, null)
        }

        val attemptPayloads = JSONArray()
        val dateFormatter = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSSXXX", Locale.US)
        attempts.forEach { attempt ->
            val answers = JSONArray()
            val storedAnswers = runCatching { JSONArray(attempt.answersJson) }.getOrDefault(JSONArray())
            for (index in 0 until storedAnswers.length()) {
                val answer = storedAnswers.optJSONObject(index) ?: continue
                val selectedAnswer = (answer.opt("selected_answer") as? String)?.takeIf(String::isNotBlank)
                answers.put(JSONObject()
                    .put("question_id", answer.optLong("question_id"))
                    .put("selected_answer", selectedAnswer ?: JSONObject.NULL)
                    .put("flagged", answer.optBoolean("flagged")))
            }

            attemptPayloads.put(JSONObject()
                .put("client_attempt_id", attempt.clientAttemptId)
                .put("title", attempt.title)
                .put("mode", attempt.mode)
                .put("passing_percentage", attempt.passingPercentage)
                .put("duration_seconds", attempt.durationSeconds)
                .put("completed_at", dateFormatter.format(Date(attempt.completedAt)))
                .put("answers", answers))
        }

        val response = requestJson(
            "POST",
            "${validateServerUrl(serverUrl)}/api/mobile/attempts",
            token = token,
            body = JSONObject().put("attempts", attemptPayloads),
        )
        val accepted = response.optJSONArray("accepted_client_attempt_ids") ?: JSONArray()
        val acceptedIds = (0 until accepted.length()).mapNotNull { accepted.optString(it).takeIf(String::isNotBlank) }
        val rejected = response.optJSONArray("rejected") ?: JSONArray()
        val firstError = rejected.optJSONObject(0)?.optString("message")?.takeIf(String::isNotBlank)

        MobileAttemptSyncResult(acceptedIds, rejected.length(), firstError)
    }

    suspend fun revoke(serverUrl: String, token: String) = withContext(Dispatchers.IO) {
        requestJson("DELETE", "${validateServerUrl(serverUrl)}/api/mobile/session", token = token)
    }

    private fun validateServerUrl(serverUrl: String): String {
        val normalizedUrl = serverUrl.trim().trimEnd('/')
        val url = runCatching { URL(normalizedUrl) }.getOrElse {
            throw IOException("Enter a valid server address, such as http://192.168.1.20:8000")
        }
        if (url.protocol !in listOf("http", "https") || url.host.isBlank()) {
            throw IOException("The server address must start with http:// or https://")
        }
        return normalizedUrl
    }

    private fun requestJson(method: String, address: String, token: String? = null, body: JSONObject? = null): JSONObject {
        val connection = (URL(address).openConnection() as HttpURLConnection).apply {
            requestMethod = method
            connectTimeout = 15_000
            readTimeout = 60_000
            setRequestProperty("Accept", "application/json")
            token?.let { setRequestProperty("Authorization", "Bearer $it") }
            if (body != null) {
                doOutput = true
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
            }
        }

        return try {
            body?.let { connection.outputStream.bufferedWriter(Charsets.UTF_8).use { writer -> writer.write(it.toString()) } }
            val responseCode = connection.responseCode
            val responseStream = if (responseCode in 200..299) connection.inputStream else connection.errorStream
            val responseText = responseStream?.bufferedReader(Charsets.UTF_8)?.use { it.readText() }.orEmpty()
            if (responseCode !in 200..299) {
                val message = runCatching { JSONObject(responseText).optString("message") }.getOrNull()
                throw IOException(message?.takeIf(String::isNotBlank) ?: "Server request failed ($responseCode).")
            }
            JSONObject(responseText)
        } finally {
            connection.disconnect()
        }
    }
}
