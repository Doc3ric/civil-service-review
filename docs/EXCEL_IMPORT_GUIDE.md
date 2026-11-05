# Excel Import Guide — Civil Service Review 2027

The **Excel Import Center** is the primary method for batch-encoding examination questions into the centralized Question Bank.

---

## 1. Template Overview

Download the official Excel template directly from the application interface or via:
```
GET /questions/template/download
```
File Name: `civil-service-question-template.xlsx`

### Expected Columns

| Column | Required | Allowed Values / Format | Description |
|---|---|---|---|
| `category` | **Yes** | `VERBAL`, `ANALYTICAL`, `NUMERICAL`, `GENERAL` | Subject division |
| `question` | **Yes** | String (minimum 5 characters) | The full question text |
| `choice_a` | **Yes** | String | Text for Option A |
| `choice_b` | **Yes** | String | Text for Option B |
| `choice_c` | **Yes** | String | Text for Option C |
| `choice_d` | **Yes** | String | Text for Option D |
| `correct_answer` | **Yes** | `A`, `B`, `C`, or `D` (case-insensitive) | The correct choice letter |
| `explanation` | No | String | Detailed rationale/explanation |
| `difficulty` | No | `Easy`, `Medium`, `Hard` (default: Medium) | Perceived item difficulty |
| `source` | No | String | Citation or reviewer origin |

---

## 2. Validation Rules

During upload, every row is inspected against strict schema rules:
1. **Required Fields**: `category`, `question`, `choice_a`, `choice_b`, `choice_c`, `choice_d`, and `correct_answer` must not be blank.
2. **Category Code**: Must map to one of the 4 recognized categories (`VERBAL`, `ANALYTICAL`, `NUMERICAL`, `GENERAL`). Full names (e.g. `Verbal Ability`) are also automatically mapped.
3. **Correct Answer Key**: Must strictly equal `A`, `B`, `C`, or `D`. Any other character causes row rejection.
4. **Duplicate Detection**: The system normalizes the question text (stripping leading/trailing spaces, converting to lowercase, and collapsing multiple whitespace characters) and computes a SHA-256 hash. If an existing question with that hash exists in MySQL, or if the question appears earlier in the same file, it is flagged as `Duplicate` and skipped.

---

## 3. Step-by-Step Import Workflow

### Step 1: Upload
Navigate to **Question Management &rarr; Import Questions** (`/questions/import`). Drag and drop or browse for an `.xlsx` or `.csv` file (up to 10MB).

### Step 2: Validate & Preview
The system parses the workbook and stages it into a preview batch. You can inspect:
- Total rows processed
- Filter tabs: `All Rows`, `Valid Rows`, `Duplicate Rows`, and `Invalid Rows`
- Row-by-row error diagnostics highlighting the exact column causing failure

### Step 3: Confirmation
Click **"Confirm Import of X Valid Questions"**.
- Valid rows are inserted into `questions` and `question_choices` within a single database transaction.
- Invalid and duplicate rows are safely skipped. Existing questions are never overwritten.

### Step 4: Audit & Error Reporting
Download the Excel Error Report (`import-error-report-batch-{id}.xlsx`) listing original spreadsheet row numbers alongside exact validation error messages.

---

## 4. Sample Excel Rows

```
category    question                                           choice_a    choice_b    choice_c    choice_d    correct_answer   explanation                                                difficulty   source
VERBAL      Choose the word most opposite to EPHEMERAL.        Fleeting    Permanent   Transient   Momentary   B                Ephemeral means fleeting; permanent is the antonym.        Medium       CSC Reviewer 2027
NUMERICAL   What is 35% of 2,400?                              720         840         860         920         B                0.35 * 2400 = 840.                                         Easy         CSC Reviewer 2027
GENERAL     Under RA 6713, deadline for annual SALN filing is: March 31    April 30    May 15      Dec 31      B                Section 8 of RA 6713 mandates filing on/before April 30.   Medium       RA 6713 Guide
```
