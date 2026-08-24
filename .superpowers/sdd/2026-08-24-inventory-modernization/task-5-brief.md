# Task 5 Brief: Immutable Consumables Ledger & Concurrency Protections

## Objective
Implement the immutable stock ledger domain (`Consumable`, `Entry`, `StockEntryType`), database migration, `PostStockEntryAction` with transactional pessimistic locking (`lockForUpdate()`), idempotency key support, `InsufficientStockException`, and feature/concurrency tests in `tests/Feature/StockLedgerConcurrencyTest.php`.

## Specific Requirements
1. **Enum**:
   - `app/Enums/StockEntryType.php`:
     - `PURCHASE = 'purchase'`
     - `ISSUE = 'issue'`
     - `ADJUSTMENT_IN = 'adjustment_in'`
     - `ADJUSTMENT_OUT = 'adjustment_out'`
     - Helper method: `isInbound(): bool` (returns true for purchase and adjustment_in), `isOutbound(): bool` (returns true for issue and adjustment_out).
2. **Migration**:
   - `database/migrations/2026_08_24_000004_create_consumables_and_entries_tables.php`:
     - `consumables`: `id`, `name`, `sku` (nullable), `unit` (string, default 'piece'), `in_stock` (unsignedInteger, default 0), `min_quantity` (unsignedInteger, nullable), `max_quantity` (unsignedInteger, nullable), `timestamps`, `deleted_at`.
     - `entries`: `id`, `consumable_id` (foreignId `consumables`), `type` (string), `quantity` (unsignedInteger), `stock_after` (unsignedInteger), `recipient_official_id` (nullable foreignId `officials`), `recorded_by` (foreignId `users`), `remarks` (text, nullable), `idempotency_key` (string, nullable, unique), `legacy_id` (bigint, nullable, unique), `created_at` (timestamp, default now). Index on `(consumable_id, created_at)`.
3. **Models**:
   - `app/Models/Consumable.php`:
     - `$fillable = ['name', 'sku', 'unit', 'in_stock', 'min_quantity', 'max_quantity']`.
     - Relations: `entries()`, `latestEntry()`.
     - Helpers/Scopes: `isLowStock(): bool` (`in_stock <= min_quantity`), `scopeLowStock($query)`.
     - ActivityLog: Spatie v4/v5 `getActivitylogOptions(): LogOptions`.
   - `app/Models/Entry.php`:
     - Disable `updated_at` (set `const UPDATED_AT = null` or custom timestamps) to enforce ledger immutability.
     - `$fillable = ['consumable_id', 'type', 'quantity', 'stock_after', 'recipient_official_id', 'recorded_by', 'remarks', 'idempotency_key', 'legacy_id', 'created_at']`.
     - Casts: `type => StockEntryType::class`, `quantity => 'integer'`, `stock_after => 'integer'`.
     - Relations: `consumable()`, `recipient()`, `recorder()`.
4. **Exceptions & Actions**:
   - `app/Exceptions/InsufficientStockException.php`: Exception thrown when issue quantity exceeds available stock.
   - `app/Services/Inventory/PostStockEntryAction.php`:
     - Signature: `execute(Consumable $consumable, StockEntryType $type, int $quantity, ?Official $recipient, User $user, ?string $remarks = null, ?string $idempotencyKey = null): Entry`
     - Idempotency: If `$idempotencyKey` is provided and an entry with that key exists, return it immediately without re-mutating stock.
     - Validation: Quantity must be $> 0$.
     - DB transaction + `Consumable::where('id', $consumable->id)->lockForUpdate()->first()`.
     - Verify stock availability on outbound entries.
     - Mutate `in_stock` and record immutable `Entry` with `stock_after`.
5. **Tests**:
   - `tests/Feature/StockLedgerConcurrencyTest.php`:
     - Test purchase increments stock and records correct `stock_after`.
     - Test issue decrements stock and records correct `stock_after`.
     - Test insufficient stock throws `InsufficientStockException` and rollback keeps balance untouched.
     - Test idempotency key prevents duplicate transactions.
     - Test adjustment_in and adjustment_out.
     - Test `scopeLowStock()` query scope.

## Output Report Contract
Write execution report to:
`.superpowers/sdd/2026-08-24-inventory-modernization/task-5-report.md`
Return in chat only status (`DONE` / `BLOCKED`), list of touched files, and a one-line summary.
