package com.civilservicereviewer.mobile

import android.content.Context
import androidx.room.Dao
import androidx.room.Database
import androidx.room.Entity
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.PrimaryKey
import androidx.room.Query
import androidx.room.Room
import androidx.room.RoomDatabase
import androidx.room.Transaction
import androidx.room.migration.Migration
import androidx.sqlite.db.SupportSQLiteDatabase
import kotlinx.coroutines.flow.Flow

@Entity(tableName = "questions")
data class LocalQuestion(
    @PrimaryKey val id: Long,
    val category: String,
    val topic: String,
    val prompt: String,
    val choiceA: String,
    val choiceB: String,
    val choiceC: String,
    val choiceD: String,
    val correctAnswer: String,
    val explanation: String,
    val difficulty: String,
    val source: String,
)

@Entity(tableName = "practice_attempts")
data class PracticeAttempt(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val title: String,
    val category: String,
    val questionCount: Int,
    val correctCount: Int,
    val completedAt: Long = System.currentTimeMillis(),
)

@Entity(tableName = "exam_settings")
data class LocalExamSettings(
    @PrimaryKey val id: Int = 1,
    val title: String,
    val totalQuestions: Int,
    val durationMinutes: Int,
    val passingPercentage: Double,
    val categoryAllocationsJson: String,
    val revision: String,
    val syncedAt: Long,
)

@Entity(tableName = "active_practice_session")
data class ActivePracticeSession(
    @PrimaryKey val id: Int = 1,
    val questionIdsJson: String,
    val answersJson: String,
    val flaggedQuestionIndexesJson: String,
    val currentQuestionIndex: Int,
    val category: String,
    val deadlineAt: Long,
)

@Dao
interface ReviewerDao {
    @Query("SELECT * FROM questions ORDER BY category, topic, id")
    fun observeQuestions(): Flow<List<LocalQuestion>>

    @Query("SELECT * FROM questions WHERE category = :category ORDER BY RANDOM() LIMIT :limit")
    suspend fun getRandomQuestions(category: String, limit: Int): List<LocalQuestion>

    @Query("SELECT * FROM questions ORDER BY RANDOM() LIMIT :limit")
    suspend fun getRandomExamQuestions(limit: Int): List<LocalQuestion>

    @Query("SELECT * FROM practice_attempts ORDER BY completedAt DESC")
    fun observeAttempts(): Flow<List<PracticeAttempt>>

    @Query("SELECT * FROM exam_settings WHERE id = 1 LIMIT 1")
    fun observeExamSettings(): Flow<LocalExamSettings?>

    @Query("SELECT * FROM active_practice_session WHERE id = 1 LIMIT 1")
    suspend fun getActivePracticeSession(): ActivePracticeSession?

    @Query("DELETE FROM questions")
    suspend fun clearQuestions()

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun saveQuestions(questions: List<LocalQuestion>)

    @Insert
    suspend fun saveAttempt(attempt: PracticeAttempt)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun saveExamSettings(settings: LocalExamSettings)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun saveActivePracticeSession(session: ActivePracticeSession)

    @Query("DELETE FROM active_practice_session WHERE id = 1")
    suspend fun clearActivePracticeSession()

    @Transaction
    suspend fun replaceQuestionBank(questions: List<LocalQuestion>, settings: LocalExamSettings) {
        clearQuestions()
        saveQuestions(questions)
        saveExamSettings(settings)
    }
}

@Database(entities = [LocalQuestion::class, PracticeAttempt::class, LocalExamSettings::class, ActivePracticeSession::class], version = 3, exportSchema = false)
abstract class ReviewerDatabase : RoomDatabase() {
    abstract fun reviewerDao(): ReviewerDao

    companion object {
        @Volatile private var instance: ReviewerDatabase? = null

        fun get(context: Context): ReviewerDatabase = instance ?: synchronized(this) {
            instance ?: Room.databaseBuilder(
                context.applicationContext,
                ReviewerDatabase::class.java,
                "civil-service-reviewer.db",
            ).addMigrations(MIGRATION_1_2, MIGRATION_2_3).build().also { instance = it }
        }

        private val MIGRATION_1_2 = object : Migration(1, 2) {
            override fun migrate(database: SupportSQLiteDatabase) {
                database.execSQL(
                    """CREATE TABLE IF NOT EXISTS exam_settings (
                        id INTEGER NOT NULL PRIMARY KEY,
                        title TEXT NOT NULL,
                        totalQuestions INTEGER NOT NULL,
                        durationMinutes INTEGER NOT NULL,
                        passingPercentage REAL NOT NULL,
                        categoryAllocationsJson TEXT NOT NULL,
                        revision TEXT NOT NULL,
                        syncedAt INTEGER NOT NULL
                    )""".trimIndent()
                )
            }
        }

        private val MIGRATION_2_3 = object : Migration(2, 3) {
            override fun migrate(database: SupportSQLiteDatabase) {
                database.execSQL(
                    """CREATE TABLE IF NOT EXISTS active_practice_session (
                        id INTEGER NOT NULL PRIMARY KEY,
                        questionIdsJson TEXT NOT NULL,
                        answersJson TEXT NOT NULL,
                        flaggedQuestionIndexesJson TEXT NOT NULL,
                        currentQuestionIndex INTEGER NOT NULL,
                        category TEXT NOT NULL,
                        deadlineAt INTEGER NOT NULL
                    )""".trimIndent()
                )
            }
        }
    }
}
