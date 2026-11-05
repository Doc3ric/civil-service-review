# Examination Engine Specification — Civil Service Review 2027

## 1. Examination Blueprint System

The examination generator reads configurable blueprints from the `exam_blueprints` and `exam_blueprint_categories` tables. The default configuration replicates the official CSC Professional examination:

- **Total Items**: 150
- **Duration**: 170 Minutes (2 hours and 50 minutes)
- **Passing Standard**: 80.00% (120 correct answers required)
- **Allocations**:
  - **Verbal Ability**: 45 Items (30.00%)
  - **Analytical Ability**: 53 Items (35.33%)
  - **Numerical Ability**: 45 Items (30.00%)
  - **General Information**: 7 Items (4.67%)
  - **Total**: 150 Items (100.00%)

---

## 2. Selection & Randomization Algorithms

### Question Selection
`QuestionSelectionService` queries published questions (`status = 'published'`) per category:
1. Validates that the Question Bank contains at least the required question count for each category.
2. Performs an `inRandomOrder()` selection limited to the blueprint quota.
3. Shuffles all 150 items across the examination so subjects appear intermixed naturally.

### Choice Randomization
`ChoiceRandomizerService` prevents rote memorization of letter keys:
1. The 4 choices are shuffled in random order for each attempt.
2. New sequential labels `A`, `B`, `C`, and `D` are assigned.
3. The correct choice label is dynamically identified and saved in `correct_choice_label_snapshot`.
4. Choices are saved into `shuffled_choices_json` on the `exam_attempt_questions` record.

---

## 3. Immutability & Security Architecture

1. **Snapshot Preservation**:
   - `question_text_snapshot`
   - `category_name_snapshot`
   - `explanation_snapshot`
   - `correct_choice_label_snapshot`
   - `shuffled_choices_json`
   Even if the original question in the Question Bank is edited or archived in the future, the candidate's historical exam results and answer keys will never mutate.

2. **Hidden Answer Keys**:
   During active testing, correct answer labels are never sent to the client browser in Livewire state or DOM elements. The runner only sends the candidate's selected choice.

3. **Server-Authoritative Timer**:
   - `started_at`: Timestamp when the attempt was created.
   - `duration_minutes`: Configured blueprint duration.
   - `deadline_at`: `started_at + duration_minutes`.
   - The browser displays a countdown timer using Alpine.js based on `remainingSeconds()`. When `now() >= deadline_at`, the server automatically finalizes the exam and marks the status as `Expired`.

---

## 4. Scoring Algorithm

Scoring runs in `ExamScoringService`:
```
Percentage = (Correct Answers / Total Questions) × 100
```
- Example: `112 / 150 = 74.67%`
- Computed fields:
  - `total_questions`
  - `correct_count`
  - `incorrect_count`
  - `unanswered_count`
  - `score_percentage` (rounded to 2 decimal places)
  - `is_passed`: `score_percentage >= passing_percentage`
  - `category_breakdown`: Detailed accuracy percentage and counts per category.
