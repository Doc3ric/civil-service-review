# Civil Service Review 2027

A personal, production-grade Civil Service Examination preparation platform built with **Laravel 13**, **Livewire 4**, **Tailwind CSS 4**, and **MySQL**, designed to prepare candidates for the Philippine Civil Service Commission (CSC) Professional Level Examination.

---

## Key Features

1. **Excel Question Import Center**
   - Download standardized Excel import template (`civil-service-question-template.xlsx`).
   - Drag-and-drop workbook upload (`.xlsx`, `.csv`).
   - Server-side pre-import validation and interactive preview table.
   - High-throughput ingestion: 1,000 questions in under 2.5 seconds with in-memory $O(1)$ hash deduplication.
   - Atomic database transactions and downloadable batch error reports.

2. **Centralized Question Bank**
   - Stores questions permanently in relational database structures.
   - Advanced search by keywords across question prompts, answer choices, and references.
   - Multi-facet filtering by examination category, difficulty (Easy, Medium, Hard), and status (Published, Draft, Archived).
   - Full CRUD operations with modal preview and answer explanation view.

3. **Full 150-Item Practice Examination**
   - Configurable examination blueprint mirroring the CSC Professional exam:
     - **Verbal Ability**: 45 items (30%)
     - **Analytical Ability**: 53 items (35%)
     - **Numerical Ability**: 45 items (30%)
     - **General Information**: 7 items (5%)
     - **Total**: 150 items, 170 minutes duration, 80.00% passing threshold.
   - Dynamic question selection and choice randomization.
   - Server-authoritative timer and automatic submission upon expiration.
   - Real-time answer persistence and interactive 150-question navigation palette.
   - Pessimistic locking (`lockForUpdate()`) preventing duplicate or concurrent submissions.

4. **Category-Based Practice**
   - Focused practice on individual subject categories.
   - Flexible question quotas: 10, 20, 30, 50, or all available items.
   - Instant scoring and comprehensive review.

5. **Examination Results & Review**
   - Automatic percentage scoring: `(Correct Answers / Total Questions) × 100`.
   - Comprehensive item-by-item review with color indicators (Green for Correct, Red for Incorrect, Gray for Unanswered).
   - Immutable question and choice snapshots preserved per attempt (master edits do not affect past results).
   - Subject category score breakdown and identification of strongest vs. weakest domains.

6. **Streak Calendar & Daily Progress**
   - Interactive monthly heatmap activity calendar.
   - Strict `Asia/Manila` timezone consistency tracking.
   - Current streak and longest streak tracking across month/year boundaries.
   - Daily question goal tracking (configurable, default 20 questions/day).

7. **Performance Analytics Dashboard**
   - Historical score progression line charts using Chart.js.
   - Category accuracy bar charts.
   - Priority review recommendations based on empirical performance.

---

## Technology Stack

- **Backend**: Laravel 13 (`laravel/framework v13.40.0`), PHP 8.3+, Eloquent ORM, Service Layer Pattern
- **Frontend**: Livewire 4 (`v4.4.8`), Alpine.js, Tailwind CSS 4 (`@tailwindcss/vite`), Vite
- **Database**: MySQL 8 (SQLite supported for testing/sandboxed CLI)
- **Spreadsheets**: PhpSpreadsheet 5.10
- **Charts**: Chart.js 4
- **Icons**: Lucide Icons
- **Testing**: PHPUnit 12

---

## Installation & Setup

### 1. Clone & Install Dependencies

```bash
composer install
npm install
```

### 2. Configure Environment

Copy `.env.example` to `.env` and set your database credentials:

```ini
APP_NAME="Civil Service Review 2027"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=civil_service_review
DB_USERNAME=root
DB_PASSWORD=
```

Generate an application key:

```bash
php artisan key:generate
```

### 3. Run Migrations & Seed Database

```bash
php artisan migrate --seed
```

The database seeder creates:
- The 4 official CSC subject categories.
- The 150-item examination blueprint.
- A default reviewer account:
  - **Email**: `reviewer@csc2027.ph`
  - **Password**: `Reviewer2027!` (in local environment; requires `INITIAL_REVIEWER_PASSWORD` or auto-generates in production).
- 150 realistic practice questions with detailed explanations.

### 4. Build Frontend Assets

```bash
npm run build
```

To run in development watch mode:

```bash
npm run dev
```

### 5. Start Application Server

```bash
php artisan serve
```

Access the application in your browser at `http://localhost:8000`.

---

## Administrative & Maintenance Commands

### Automated Database Backup & Pruning
```bash
php artisan app:backup-database {--prune-days=7}
```
Executes an atomic snapshot of MySQL or SQLite, calculates a SHA-256 integrity checksum, and prunes archives older than 7 days.

### Rotate / Reset Reviewer Password
```bash
php artisan app:reset-reviewer-password reviewer@csc2027.ph [--password=YourNewPassword]
```
Safely changes the reviewer password or generates a 24-character random password.

---

## Running Automated Tests

Run the complete test suite with PHPUnit:

```bash
php artisan test
```

**Results**: **41 passed**, 158 assertions (100% passing rate) covering:
- Question Bank CRUD, search, and category filtering.
- High-volume Excel import stress tests (100, 500, 1,000 items) and edge-case sanitization.
- 150-question generation, quota blueprint enforcement, and choice randomization.
- Examination reliability (browser refresh recovery, simultaneous answer idempotency, deadline auto-submission).
- Score percentage formula calculation and result rationales.
- Streak calculation with strict `Asia/Manila` timezone consistency across month/year boundaries.
- Security controls: rate-limited login, tenant isolation, and active exam answer confidentiality.

---

## Documentation Library

Detailed engineering documentation is located in the `docs/` directory:

- [Project Architecture](docs/PROJECT_ARCHITECTURE.md)
- [Database Schema & Relational Design](docs/DATABASE_SCHEMA.md)
- [Excel Import Specification & Guide](docs/EXCEL_IMPORT_GUIDE.md)
- [Examination Engine Specification](docs/EXAMINATION_ENGINE.md)
- [Production Readiness Audit](docs/PRODUCTION_READINESS_AUDIT.md)
- [Security Audit & Hardening Report](docs/SECURITY_AUDIT.md)
- [Performance & Stress Audit Report](docs/PERFORMANCE_AUDIT.md)
- [Backup & Disaster Recovery Runbook](docs/BACKUP_AND_RECOVERY.md)
- [Development Progress Log](docs/DEVELOPMENT_PROGRESS.md)

---

## Design System

Inspired by the Philippine Civil Service Examination visual identity:
- **Primary Red**: `#C92535`
- **Navy Blue**: `#174A8B`
- **Accent Blue**: `#2563EB`
- **Background**: `#F8FAFC`
- **Primary Text**: `#1E293B`
- **Borders**: `#E2E8F0`
