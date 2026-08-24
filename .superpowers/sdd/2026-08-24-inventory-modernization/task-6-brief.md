# Task 6 Brief: Agreements & Idempotent Payment Schedules

## Objective
Implement vendor agreements and idempotent payment schedule generation (`Agreement`, `Payment`, `PaymentStatus`), database migration, `GeneratePaymentScheduleAction` with deterministic `schedule_key`, `CompletePaymentAction`, and feature tests in `tests/Feature/AgreementPaymentScheduleTest.php`.

## Specific Requirements
1. **Enum**:
   - `app/Enums/PaymentStatus.php`:
     - `PENDING = 'pending'`
     - `COMPLETED = 'completed'`
     - `CANCELLED = 'cancelled'`
     - Helper methods for badge colors and labels.
2. **Migration**:
   - `database/migrations/2026_08_24_000005_create_agreements_and_payments_tables.php`:
     - `agreements`: `id`, `name`, `agency`, `file_id` (nullable foreignId `files` on delete set null), `type` (string), `expiry` (date, nullable), `annual_cost` (decimal(15,2), nullable), `currency` (char(3), default 'INR'), `billing_interval_months` (unsignedTinyInteger, nullable), `billing_anchor_date` (date, nullable), `paid_till` (date, nullable), `remarks` (text, nullable), `legacy_payload` (json, nullable), `timestamps`, `deleted_at`. Index on `expiry`.
     - `payments`: `id`, `agreement_id` (foreignId `agreements` on delete cascade), `amount` (decimal(15,2), nullable), `currency` (char(3), default 'INR'), `due_date` (date), `paid_date` (date, nullable), `status` (string, default 'pending'), `invoice_number` (string, nullable), `remarks` (text, nullable), `schedule_key` (string, unique), `completed_by` (nullable foreignId `users` on delete set null), `legacy_id` (bigint, nullable, unique), `timestamps`. Index on `(agreement_id, due_date)`, `(status, due_date)`.
3. **Models**:
   - `app/Models/Agreement.php`:
     - `$fillable = ['name', 'agency', 'file_id', 'type', 'expiry', 'annual_cost', 'currency', 'billing_interval_months', 'billing_anchor_date', 'paid_till', 'remarks', 'legacy_payload']`.
     - Casts: `expiry => 'date'`, `billing_anchor_date => 'date'`, `paid_till => 'date'`, `legacy_payload => 'array'`, `annual_cost => 'decimal:2'`.
     - Relations: `payments()`, `file()`, `attachments()`.
     - Scopes: `scopeExpiringSoon($query, $days = 180)`, `scopeExpired($query)`.
     - ActivityLog: Spatie v4/v5 `getActivitylogOptions(): LogOptions`.
   - `app/Models/Payment.php`:
     - `$fillable = ['agreement_id', 'amount', 'currency', 'due_date', 'paid_date', 'status', 'invoice_number', 'remarks', 'schedule_key', 'completed_by', 'legacy_id']`.
     - Casts: `status => PaymentStatus::class`, `due_date => 'date'`, `paid_date => 'date'`, `amount => 'decimal:2'`.
     - Relations: `agreement()`, `completedBy()`.
     - Scopes: `scopePending($query)`, `scopeOverdue($query)`, `scopeCompleted($query)`.
4. **Service Actions (`app/Services/Agreements/`)**:
   - `GeneratePaymentScheduleAction.php`:
     - Signature: `execute(Agreement $agreement): Collection`
     - Validates `billing_anchor_date`, `expiry`, and `billing_interval_months` (1-12).
     - Loops from `billing_anchor_date` up to `expiry` stepping by `billing_interval_months`.
     - Handles month-end anchoring (e.g. Jan 31 $\rightarrow$ Feb 28/29 $\rightarrow$ March 31).
     - Calculates milestone amount = `annual_cost * (interval_months / 12)`.
     - Generates `schedule_key = sha1("{$agreement->id}-{$dueDate->format('Y-m-d')}")`.
     - Uses `firstOrCreate` / upsert on `schedule_key` so re-running never creates duplicate records or overrides completed payments.
   - `CompletePaymentAction.php`:
     - Signature: `execute(Payment $payment, User $user, string $invoiceNumber, ?Carbon $paidDate = null): Payment`
     - Runs in DB transaction, updates payment status to `COMPLETED`, records `paid_date` and `completed_by`, and updates `agreement.paid_till = max(paid_till, payment.due_date)`.
5. **Tests**:
   - `tests/Feature/AgreementPaymentScheduleTest.php`:
     - Test quarterly, monthly, and semi-annual payment schedule generation.
     - Test idempotency (re-running does not duplicate payments).
     - Test month-end date calculations.
     - Test payment completion workflow and `paid_till` updates.
     - Test `scopeExpiringSoon` and `scopeOverdue` query scopes.

## Output Report Contract
Write execution report to:
`.superpowers/sdd/2026-08-24-inventory-modernization/task-6-report.md`
Return in chat only status (`DONE` / `BLOCKED`), list of touched files, and a one-line summary.
