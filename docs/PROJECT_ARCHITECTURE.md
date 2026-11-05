# Project Architecture — Civil Service Review 2027

## 1. Architectural Overview

The application is structured as a **Modular Monolith** using Laravel 13, Livewire 4, and Tailwind CSS 4. The architecture isolates business rules and calculation algorithms into dedicated Service classes, keeping Livewire components focused on UI state management and user interaction.

```
app/
├── Enums/                 # Strict string-backed business enums
├── Http/
│   └── Controllers/       # Lightweight controllers for Auth and File streaming
├── Livewire/              # Interactive UI components by functional domain
│   ├── Analytics/
│   ├── Dashboard/
│   ├── Examinations/
│   ├── Questions/
│   ├── Results/
│   ├── Settings/
│   └── Streaks/
├── Models/                # Eloquent models with strict relations and type casts
└── Services/              # Domain business logic layer
    ├── Examination/       # Generation, randomization, scoring, submission, review
    ├── Import/            # Excel template, validation, duplicate detection, batch import
    ├── Progress/          # Activity recording, streaks, analytics aggregation
    └── QuestionBank/      # Question lifecycle, search, filtering
```

---

## 2. Module Responsibilities

### Question Management Module
- **QuestionManagementService**: Handles paginated querying, multi-facet filtering (category, status, difficulty), keyword search, and CRUD operations with 4 answer choices per question.
- **Enums**: `CategoryCode`, `QuestionDifficulty`, `QuestionStatus`.

### Excel Import Center
- **QuestionImportService**: Generates standardized Excel templates using PhpSpreadsheet and orchestrates batch uploads.
- **QuestionValidationService**: Enforces schema rules: required fields, choice completeness, case-insensitive category mapping, and correct answer validity (A, B, C, D).
- **DuplicateDetectionService**: Normalizes question text (lowercased, whitespace-collapsed) and evaluates SHA-256 content hashes against existing records.

### Examination Engine
- **ExamGeneratorService**: Coordinates blueprint distribution, question selection, choice scrambling, and snapshot generation.
- **QuestionSelectionService**: Checks question bank availability per category before generating examinations; selects random published questions.
- **ChoiceRandomizerService**: Shuffles choices A, B, C, and D while dynamically mapping the correct answer key.
- **ExamScoringService**: Calculates score percentage, category breakdowns, and pass/fail status.
- **ExamSubmissionService**: Handles answer persistence, checks server deadlines, finalizes attempts, and triggers study activity recording.
- **ExamReviewService**: Generates review datasets with immutable snapshots and rationales.

### Progress & Streak Tracking
- **StudyActivityService**: Records qualifying study activities tied to completed exam attempts with duration and score.
- **StreakCalculationService**: Computes current and longest streaks in `Asia/Manila` time, evaluates month and year boundaries, and renders the monthly activity heatmap.
- **PerformanceAnalyticsService**: Computes cross-examination statistics, domain accuracy, score progression trends, and focus area recommendations.

---

## 3. Data Integrity & Immutability Patterns

- **Question Snapshots**: When an examination attempt is generated, `ExamAttemptQuestion` saves immutable copies of the question text, category name, explanation, and shuffled choice options. If a question in the master bank is modified or archived later, past examination results and answer keys remain permanently intact.
- **Server-Authoritative Time**: The examination deadline is calculated upon creation (`deadline_at = started_at + duration_minutes`). JavaScript manages the visual countdown, but the server validates expiration on answer submission and auto-submits when time expires.
- **Atomic Operations**: Staging, batch importing, exam generation, and submission run inside database transactions (`DB::transaction`).
