package com.civilservicereviewer.mobile

import org.json.JSONArray
import org.json.JSONObject
import java.util.UUID

fun createPracticeAttempt(
    title: String,
    category: String,
    mode: String,
    quiz: List<LocalQuestion>,
    selectedAnswers: Map<Int, String>,
    flaggedIndexes: Set<Int>,
    passingPercentage: Double,
    durationSeconds: Int,
    completedAt: Long = System.currentTimeMillis(),
): PracticeAttempt {
    val correctCount = quiz.indices.count { index -> selectedAnswers[index] == quiz[index].correctAnswer }
    val categoryBreakdowns = quiz.indices.groupBy { quiz[it].category }.map { (code, indexes) ->
        val correct = indexes.count { index -> selectedAnswers[index] == quiz[index].correctAnswer }
        JSONObject()
            .put("code", code)
            .put("name", categoryDisplayName(code))
            .put("total", indexes.size)
            .put("correct", correct)
    }
    val topicBreakdowns = quiz.indices
        .filter { quiz[it].topic.isNotBlank() }
        .groupBy { index -> "${quiz[index].category}|${quiz[index].topic}" }
        .map { (_, indexes) ->
            val firstQuestion = quiz[indexes.first()]
            val correct = indexes.count { index -> selectedAnswers[index] == quiz[index].correctAnswer }
            JSONObject()
                .put("category_code", firstQuestion.category)
                .put("category_name", categoryDisplayName(firstQuestion.category))
                .put("name", firstQuestion.topic)
                .put("total", indexes.size)
                .put("correct", correct)
        }

    val answerRecords = quiz.mapIndexed { index, question ->
        val choices = JSONArray()
            .put(JSONObject().put("label", "A").put("text", question.choiceA))
            .put(JSONObject().put("label", "B").put("text", question.choiceB))
            .put(JSONObject().put("label", "C").put("text", question.choiceC))
            .put(JSONObject().put("label", "D").put("text", question.choiceD))
        val selectedAnswer = selectedAnswers[index]
        JSONObject()
            .put("question_id", question.id)
            .put("category", question.category)
            .put("category_name", categoryDisplayName(question.category))
            .put("topic", question.topic)
            .put("prompt", question.prompt)
            .put("choices", choices)
            .put("selected_answer", selectedAnswer ?: JSONObject.NULL)
            .put("correct_answer", question.correctAnswer)
            .put("is_correct", selectedAnswer == question.correctAnswer)
            .put("flagged", index in flaggedIndexes)
            .put("explanation", question.explanation)
            .put("difficulty", question.difficulty)
            .put("source", question.source)
    }

    return PracticeAttempt(
        clientAttemptId = UUID.randomUUID().toString(),
        title = title,
        category = category,
        mode = mode,
        questionCount = quiz.size,
        correctCount = correctCount,
        scorePercentage = if (quiz.isEmpty()) 0.0 else correctCount * 100.0 / quiz.size,
        passingPercentage = passingPercentage,
        durationSeconds = durationSeconds.coerceAtLeast(0),
        completedAt = completedAt,
        categoryBreakdownJson = JSONArray(categoryBreakdowns).toString(),
        topicBreakdownJson = JSONArray(topicBreakdowns).toString(),
        answersJson = JSONArray(answerRecords).toString(),
        synced = false,
    )
}

private fun categoryDisplayName(code: String): String = when (code.uppercase()) {
    "VERBAL" -> "Verbal Ability"
    "ANALYTICAL" -> "Analytical Ability"
    "NUMERICAL" -> "Numerical Ability"
    "GENERAL" -> "General Information"
    else -> code
}
