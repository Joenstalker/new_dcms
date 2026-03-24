# 10-Digit Patient ID Orchestration Plan

## Overview
The goal is to replace the default sequential auto-incrementing Patient IDs with a custom 10-digit format:
**`YYMMDD` + `ms` (4 digits)** (e.g., `2603241234`).

## Phase 1: Planning
We will achieve this entirely at the Application layer using Laravel Eloquent model hooks to generate and assign this custom ID seamlessly during record creation.

### Agent Allocation & Tasks
- **`database-architect` / `backend-specialist`**
  - Update `app/Models/Patient.php`:
    - Add `public $incrementing = false;` to prevent Eloquent from discarding the custom ID upon insert.
    - Inside the `static::creating` boot method, implement the ID generator: Use `date('ymd')` combined with `microtime()` fractions padded to 4 digits. Loop to ensure the generated ID does not already exist in the database.
- **`frontend-specialist`**
  - Verify `Index.vue`, `PatientShowModal.vue`, and `PatientController.php` print the ID directly. Currently, they use `padStart(6, '0')`. This padding will automatically fail gracefully (leaving the 10 digit string perfectly intact), but it is safer to remove it to prevent confusion.
- **`test-engineer`**
  - Run the application, submit a new patient via the UI, and verify the resulting ID perfectly matches the `YYMMDDmsmsmsms` 10-digit spec.

## User Approval Required
Do you approve this plan? (Y/N)
- Y: Start implementation
- N: I'll revise the plan
