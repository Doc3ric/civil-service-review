# Database Schema Specification — Civil Service Review 2027

The application uses a fully normalized relational MySQL schema with foreign keys, composite indexes, and strict constraints.

---

## Entity Relationship Overview

```
users
  ├── user_settings (1:1)
  ├── question_import_batches (1:N)
  ├── exam_attempts (1:N)
  └── study_activities (1:N)

exam_categories (1:N)
  ├── questions (1:N) ── question_choices (1:4)
  └── exam_blueprint_categories (1:N)

exam_blueprints (1:N)
  ├── exam_blueprint_categories (1:N)
  └── exam_attempts (1:N)

exam_attempts (1:N)
  ├── exam_attempt_questions (1:N) ── exam_answers (1:1)
  └── study_activities (1:1)

question_import_batches (1:N)
  ├── question_import_rows (1:N)
  └── questions (1:N)
```

---

## Detailed Table Specifications

### 1. `users`
Standard Laravel user table:
- `id` (BIGINT UNSIGNED, Primary Key)
- `name` (VARCHAR)
- `email` (VARCHAR, Unique)
- `password` (VARCHAR)
- `remember_token` (VARCHAR)
- `created_at`, `updated_at` (TIMESTAMP)

### 2. `exam_categories`
- `id` (BIGINT UNSIGNED, Primary Key)
- `code` (VARCHAR(50), Unique: 'VERBAL', 'ANALYTICAL', 'NUMERICAL', 'GENERAL')
- `name` (VARCHAR)
- `description` (TEXT, Nullable)
- `icon` (VARCHAR(100), Nullable)
- `color` (VARCHAR(50), Nullable)
- `sort_order` (INT UNSIGNED, Default 0)
- `is_active` (BOOLEAN, Default true)
- `created_at`, `updated_at` (TIMESTAMP)

### 3. `questions`
- `id` (BIGINT UNSIGNED, Primary Key)
- `category_id` (BIGINT UNSIGNED, Foreign Key -> `exam_categories.id`, Cascade)
- `import_batch_id` (BIGINT UNSIGNED, Foreign Key -> `question_import_batches.id`, Nullable, Null on delete)
- `question_text` (TEXT)
- `correct_choice` (VARCHAR(5): 'A', 'B', 'C', 'D')
- `explanation` (TEXT, Nullable)
- `difficulty` (VARCHAR(20), Default 'medium': 'easy', 'medium', 'hard')
- `source` (VARCHAR, Nullable)
- `status` (VARCHAR(20), Default 'published': 'draft', 'published', 'archived')
- `content_hash` (VARCHAR(64), Indexed: SHA-256 normalized question text)
- `deleted_at` (TIMESTAMP, Nullable - Soft Deletes)
- `created_at`, `updated_at` (TIMESTAMP)
- **Indexes**: `[category_id, status]`, `[status, difficulty]`, `[content_hash]`

### 4. `question_choices`
- `id` (BIGINT UNSIGNED, Primary Key)
- `question_id` (BIGINT UNSIGNED, Foreign Key -> `questions.id`, Cascade)
- `choice_label` (VARCHAR(5): 'A', 'B', 'C', 'D')
- `choice_text` (TEXT)
- `is_correct` (BOOLEAN, Default false)
- `created_at`, `updated_at` (TIMESTAMP)
- **Unique**: `[question_id, choice_label]`
- **Index**: `[question_id, is_correct]`

### 5. `exam_blueprints`
- `id` (BIGINT UNSIGNED, Primary Key)
- `name` (VARCHAR)
- `exam_type` (VARCHAR(30), Default 'full')
- `total_questions` (INT UNSIGNED, Default 150)
- `duration_minutes` (INT UNSIGNED, Default 170)
- `passing_percentage` (DECIMAL(5, 2), Default 80.00)
- `description` (TEXT, Nullable)
- `is_active` (BOOLEAN, Default true)
- `created_at`, `updated_at` (TIMESTAMP)

### 6. `exam_blueprint_categories`
- `id` (BIGINT UNSIGNED, Primary Key)
- `exam_blueprint_id` (BIGINT UNSIGNED, Foreign Key -> `exam_blueprints.id`, Cascade)
- `category_id` (BIGINT UNSIGNED, Foreign Key -> `exam_categories.id`, Cascade)
- `question_count` (INT UNSIGNED)
- `percentage` (DECIMAL(5, 2))
- `created_at`, `updated_at` (TIMESTAMP)
- **Unique**: `[exam_blueprint_id, category_id]`

### 7. `exam_attempts`
- `id` (BIGINT UNSIGNED, Primary Key)
- `user_id` (BIGINT UNSIGNED, Foreign Key -> `users.id`, Cascade)
- `exam_blueprint_id` (BIGINT UNSIGNED, Foreign Key -> `exam_blueprints.id`, Nullable, Null on delete)
- `category_id` (BIGINT UNSIGNED, Foreign Key -> `exam_categories.id`, Nullable, Null on delete)
- `title` (VARCHAR)
- `mode` (VARCHAR(20), Default 'full': 'full', 'category')
- `total_questions` (INT UNSIGNED, Default 0)
- `correct_count` (INT UNSIGNED, Default 0)
- `incorrect_count` (INT UNSIGNED, Default 0)
- `unanswered_count` (INT UNSIGNED, Default 0)
- `score_percentage` (DECIMAL(5, 2), Default 0.00)
- `status` (VARCHAR(20), Default 'in_progress': 'in_progress', 'completed', 'expired', 'abandoned')
- `duration_minutes` (INT UNSIGNED, Default 0)
- `started_at` (TIMESTAMP)
- `deadline_at` (TIMESTAMP, Nullable)
- `submitted_at` (TIMESTAMP, Nullable)
- `category_scores_json` (JSON, Nullable)
- `created_at`, `updated_at` (TIMESTAMP)
- **Indexes**: `[user_id, status]`, `[user_id, submitted_at]`, `[mode, status]`

### 8. `exam_attempt_questions`
Immutable question snapshot table:
- `id` (BIGINT UNSIGNED, Primary Key)
- `exam_attempt_id` (BIGINT UNSIGNED, Foreign Key -> `exam_attempts.id`, Cascade)
- `question_id` (BIGINT UNSIGNED, Foreign Key -> `questions.id`, Nullable, Null on delete)
- `category_id` (BIGINT UNSIGNED, Foreign Key -> `exam_categories.id`, Cascade)
- `question_number` (INT UNSIGNED)
- `question_text_snapshot` (TEXT)
- `category_name_snapshot` (VARCHAR)
- `explanation_snapshot` (TEXT, Nullable)
- `correct_choice_label_snapshot` (VARCHAR(5))
- `shuffled_choices_json` (JSON)
- `created_at`, `updated_at` (TIMESTAMP)
- **Unique**: `[exam_attempt_id, question_number]`
- **Index**: `[exam_attempt_id, category_id]`

### 9. `exam_answers`
- `id` (BIGINT UNSIGNED, Primary Key)
- `exam_attempt_id` (BIGINT UNSIGNED, Foreign Key -> `exam_attempts.id`, Cascade)
- `exam_attempt_question_id` (BIGINT UNSIGNED, Foreign Key -> `exam_attempt_questions.id`, Cascade)
- `selected_choice_label` (VARCHAR(5), Nullable)
- `is_correct` (BOOLEAN, Default false)
- `answered_at` (TIMESTAMP, Nullable)
- `created_at`, `updated_at` (TIMESTAMP)
- **Unique**: `[exam_attempt_id, exam_attempt_question_id]`
- **Index**: `[exam_attempt_id, is_correct]`

### 10. `question_import_batches`
- `id` (BIGINT UNSIGNED, Primary Key)
- `user_id` (BIGINT UNSIGNED, Foreign Key -> `users.id`, Nullable, Null on delete)
- `original_filename` (VARCHAR)
- `storage_path` (VARCHAR, Nullable)
- `total_rows` (INT UNSIGNED, Default 0)
- `imported_rows` (INT UNSIGNED, Default 0)
- `invalid_rows` (INT UNSIGNED, Default 0)
- `duplicate_rows` (INT UNSIGNED, Default 0)
- `skipped_rows` (INT UNSIGNED, Default 0)
- `status` (VARCHAR(30), Default 'pending')
- `error_summary_json` (JSON, Nullable)
- `created_at`, `updated_at` (TIMESTAMP)
- **Index**: `[status, created_at]`

### 11. `question_import_rows`
- `id` (BIGINT UNSIGNED, Primary Key)
- `import_batch_id` (BIGINT UNSIGNED, Foreign Key -> `question_import_batches.id`, Cascade)
- `row_number` (INT UNSIGNED)
- `raw_data_json` (JSON, Nullable)
- `category_code` (VARCHAR(50), Nullable)
- `question_text` (TEXT, Nullable)
- `choice_a` (TEXT, Nullable)
- `choice_b` (TEXT, Nullable)
- `choice_c` (TEXT, Nullable)
- `choice_d` (TEXT, Nullable)
- `correct_answer` (VARCHAR(10), Nullable)
- `difficulty` (VARCHAR(20), Nullable)
- `source` (VARCHAR, Nullable)
- `status` (VARCHAR(20), Default 'valid': 'valid', 'invalid', 'duplicate', 'imported', 'skipped')
- `error_messages_json` (JSON, Nullable)
- `created_at`, `updated_at` (TIMESTAMP)
- **Indexes**: `[import_batch_id, status]`, `[import_batch_id, row_number]`

### 12. `study_activities`
- `id` (BIGINT UNSIGNED, Primary Key)
- `user_id` (BIGINT UNSIGNED, Foreign Key -> `users.id`, Cascade)
- `exam_attempt_id` (BIGINT UNSIGNED, Foreign Key -> `exam_attempts.id`, Nullable, Null on delete)
- `activity_date` (DATE)
- `activity_type` (VARCHAR(30), Default 'full_exam')
- `questions_count` (INT UNSIGNED, Default 0)
- `duration_minutes` (INT UNSIGNED, Default 0)
- `score_percentage` (DECIMAL(5, 2), Nullable)
- `created_at`, `updated_at` (TIMESTAMP)
- **Unique**: `[user_id, activity_date, exam_attempt_id]`
- **Index**: `[user_id, activity_date]`

### 13. `user_settings`
- `id` (BIGINT UNSIGNED, Primary Key)
- `user_id` (BIGINT UNSIGNED, Foreign Key -> `users.id`, Cascade, Unique)
- `daily_question_target` (INT UNSIGNED, Default 20)
- `daily_study_goal_minutes` (INT UNSIGNED, Default 30)
- `timezone` (VARCHAR(50), Default 'Asia/Manila')
- `preferences_json` (JSON, Nullable)
- `created_at`, `updated_at` (TIMESTAMP)
