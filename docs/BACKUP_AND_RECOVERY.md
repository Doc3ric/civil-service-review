# Backup & Disaster Recovery Runbook — Civil Service Review 2027

**Document Version**: 1.0  
**Last Updated**: November 4, 2026  
**Audience**: DevOps Engineers, System Administrators, Application Maintainers  

---

## 1. Overview & Strategy

The **Civil Service Review 2027** platform maintains critical persistent data:
1. **Question Bank**: Master questions, randomized choices, explanations, difficulty ratings, and categories.
2. **Examination Records**: In-progress and completed attempts, server-enforced timers, immutable snapshotted question text, and student answers.
3. **Study Progress & Streaks**: Historical daily study activity entries and calculated consistency streaks.
4. **Audit Trail**: Excel import batches and staged row logs.

This document outlines automated procedures, manual failover commands, storage locations, verification checksums, and retention rules to guarantee zero data loss.

---

## 2. Automated Backup Implementation

### 2.1. The Artisan Backup Command

The application provides a dedicated maintenance command:
```bash
php artisan app:backup-database {--prune-days=7}
```

#### Key Capabilities:
- **Zero Hardcoded Credentials**: Reads database connection parameters directly from the runtime configuration (`config/database.php` populated from `.env`).
- **MySQL & SQLite Native Support**:
  - In **MySQL** mode, executes `mysqldump` with `--single-transaction`, `--quick`, `--routines`, and `--triggers` to capture an ACID-consistent snapshot without table locks.
  - In **SQLite** mode, performs an atomic filesystem copy of the active database file.
- **SHA-256 Integrity Checksum**: Automatically generates and outputs a SHA-256 hash for every backup file created.
- **Automated Retention Pruning**: Automatically purges backup archives older than the specified retention window (default: 7 days).

### 2.2. Scheduled Automated Execution

Configure the server's cron table to run the Laravel scheduler every minute (`crontab -e`):

```cron
* * * * * cd /path/to/civil-service-review && php artisan schedule:run >> /dev/null 2>&1
```

In `routes/console.php` (or Laravel 13 console kernel), schedule the backup command to execute daily at midnight (Asia/Manila time):

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:backup-database --prune-days=7')
    ->dailyAt('00:00')
    ->timezone('Asia/Manila')
    ->appendOutputTo(storage_path('logs/backups.log'));
```

---

## 3. Storage Hierarchy & Backup Protection

### 3.1. Local Storage Structure

All backups and raw import sources reside in isolated local storage:
```
storage/
├── app/
│   ├── backups/                  # Database SQL/SQLite snapshots
│   │   ├── backup_civil_service_review_2026-11-04_000000.sql
│   │   └── ...
│   └── imports/                  # Raw Excel/CSV upload workbooks
│       ├── batch_a1b2c3d4.xlsx
│       └── ...
└── logs/
    └── backups.log               # Backup operational audit trail
```

### 3.2. Access Protection & Permissions

- Ensure the `storage/app/backups/` directory is owned by the web/service user (`www-data` or equivalent) with directory permissions `0750` (`rwxr-x---`).
- Backups must **never** be located in or symlinked to the `public/` web root.
- Ensure off-site synchronization (e.g., AWS S3, Google Cloud Storage, or secure SFTP) executes via a dedicated service account using encrypted channels (TLS 1.3).

---

## 4. Preservation of Original Excel Files

When an administrator or reviewer uploads an Excel or CSV file via the Import Center:
1. The uploaded file is stored under `storage/app/imports/` with a non-predictable UUID.
2. The batch record in `question_import_batches` logs:
   - `original_filename`
   - `file_path`
   - `total_rows`, `valid_rows`, `invalid_rows`, `duplicate_rows`
   - `created_at`
3. Raw files are permanently retained to serve as an immutable historical source of truth, enabling full reconstruction of questions if required.

---

## 5. Step-by-Step Restoration Procedures

### 5.1. MySQL Database Restoration

When restoring a MySQL production database from a `.sql` backup snapshot:

#### Step 1: Put Application into Maintenance Mode
```bash
php artisan down --message="Database maintenance and restoration in progress. Please return shortly."
```

#### Step 2: Verify Backup Integrity
Verify the SHA-256 checksum of the target backup archive against your backup audit log:
```bash
# On Linux / macOS:
sha256sum storage/app/backups/backup_civil_service_review_2026-11-04_000000.sql

# On Windows PowerShell:
Get-FileHash storage/app/backups/backup_civil_service_review_2026-11-04_000000.sql -Algorithm SHA256
```

#### Step 3: Execute MySQL Import
Import the verified snapshot directly into the target database using the credentials from `.env`:
```bash
mysql -h 127.0.0.1 -u root -p civil_service_review < storage/app/backups/backup_civil_service_review_2026-11-04_000000.sql
```

#### Step 4: Run Migrations & Clear Caches
Ensure any intermediate migrations are synchronized:
```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 5: Resume Application
```bash
php artisan up
```

---

### 5.2. SQLite Database Restoration

When restoring in SQLite mode (e.g. staging or local evaluation):

#### Interactive Command:
```bash
php artisan app:backup-database --restore=storage/app/backups/backup_2026-11-04_120000.sqlite
```

#### Manual Restoration:
```bash
# Stop application
php artisan down

# Copy backup over database file
cp storage/app/backups/backup_2026-11-04_120000.sqlite database/database.sqlite

# Bring application back up
php artisan up
```

---

## 6. Disaster Recovery Drill & Verification

### Drill Schedule
Conduct a disaster recovery simulation **once per quarter**:
1. Stand up an isolated sandbox database instance.
2. Download the latest automated backup from storage.
3. Execute the restoration steps outlined in Section 5.
4. Run the automated test suite to confirm operational integrity:
   ```bash
   php artisan test
   ```
5. Verify that:
   - Question bank record counts match previous benchmarks.
   - User exam histories and past percentage scores match exact records.
   - Streak calendar accurately renders consecutive study days.

---

## 7. Recovery Checklist

- [x] Automated Artisan backup command (`app:backup-database`) tested and functional.
- [x] SHA-256 checksum validation implemented.
- [x] Retention policy set to prune backups after 7 days.
- [x] Sensitive credentials completely isolated to `.env`.
- [x] Original Excel import files preserved in `storage/app/imports/`.
- [x] Step-by-step restoration procedures verified for MySQL and SQLite.
