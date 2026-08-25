<?php

namespace Tests\Feature;

use App\Enums\StockEntryType;
use App\Enums\UserRole;
use App\Exceptions\InsufficientStockException;
use App\Models\Consumable;
use App\Models\Entry;
use App\Models\Official;
use App\Models\User;
use App\Services\Inventory\PostStockEntryAction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Activitylog\Support\LogOptions;
use Tests\TestCase;

class StockLedgerConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test StockEntryType enum cases, values, labels, and direction helpers.
     */
    public function test_stock_entry_type_enum_behaviors_and_helpers(): void
    {
        $this->assertEquals(['purchase', 'issue', 'adjustment_in', 'adjustment_out'], StockEntryType::values());
        $this->assertEquals('Purchase', StockEntryType::PURCHASE->label());
        $this->assertEquals('Issue', StockEntryType::ISSUE->label());
        $this->assertEquals('Adjustment In', StockEntryType::ADJUSTMENT_IN->label());
        $this->assertEquals('Adjustment Out', StockEntryType::ADJUSTMENT_OUT->label());

        $this->assertTrue(StockEntryType::PURCHASE->isInbound());
        $this->assertFalse(StockEntryType::PURCHASE->isOutbound());

        $this->assertTrue(StockEntryType::ADJUSTMENT_IN->isInbound());
        $this->assertFalse(StockEntryType::ADJUSTMENT_IN->isOutbound());

        $this->assertTrue(StockEntryType::ISSUE->isOutbound());
        $this->assertFalse(StockEntryType::ISSUE->isInbound());

        $this->assertTrue(StockEntryType::ADJUSTMENT_OUT->isOutbound());
        $this->assertFalse(StockEntryType::ADJUSTMENT_OUT->isInbound());

        $this->assertArrayHasKey('purchase', StockEntryType::labels());
    }

    /**
     * Test Consumable model casts, relations, and activity logging.
     */
    public function test_consumable_model_casts_relations_and_scopes(): void
    {
        $consumable = Consumable::create([
            'name' => 'A4 Paper (Box)',
            'sku' => 'CON-A4-01',
            'unit' => 'box',
            'in_stock' => 20,
            'min_quantity' => 5,
            'max_quantity' => 100,
        ]);

        $this->assertEquals('A4 Paper (Box)', $consumable->name);
        $this->assertEquals('CON-A4-01', $consumable->sku);
        $this->assertEquals('box', $consumable->unit);
        $this->assertSame(20, $consumable->in_stock);
        $this->assertSame(5, $consumable->min_quantity);
        $this->assertSame(100, $consumable->max_quantity);

        $this->assertInstanceOf(HasMany::class, $consumable->entries());
        $this->assertInstanceOf(HasOne::class, $consumable->latestEntry());
        $this->assertInstanceOf(HasOne::class, $consumable->latestentry());

        $options = $consumable->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);
    }

    /**
     * Test Entry model immutability, casting, and relations.
     */
    public function test_entry_model_ledger_immutability_and_relations(): void
    {
        $this->assertNull(Entry::UPDATED_AT, 'Entry model must have UPDATED_AT set to null for immutability.');

        $consumable = Consumable::factory()->inStock(10)->create();
        $official = Official::factory()->create();
        $user = User::factory()->create(['role' => UserRole::STOCK_OPERATOR]);

        $entry = Entry::create([
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::PURCHASE,
            'quantity' => 10,
            'stock_after' => 20,
            'recipient_official_id' => $official->id,
            'recorded_by' => $user->id,
            'remarks' => 'Initial batch purchase',
        ]);

        $this->assertSame(StockEntryType::PURCHASE, $entry->type);
        $this->assertSame(10, $entry->quantity);
        $this->assertSame(20, $entry->stock_after);

        $this->assertInstanceOf(BelongsTo::class, $entry->consumable());
        $this->assertInstanceOf(BelongsTo::class, $entry->recipient());
        $this->assertInstanceOf(BelongsTo::class, $entry->recorder());
        $this->assertInstanceOf(BelongsTo::class, $entry->issuer());

        $this->assertEquals($consumable->id, $entry->consumable->id);
        $this->assertEquals($official->id, $entry->recipient->id);
        $this->assertEquals($user->id, $entry->recorder->id);

        $options = $entry->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);
    }

    /**
     * Test purchase entry increments consumable stock and records accurate ledger stock_after.
     */
    public function test_purchase_entry_increments_stock_and_records_correct_stock_after(): void
    {
        $user = User::factory()->create(['role' => UserRole::STOCK_OPERATOR]);
        $consumable = Consumable::factory()->inStock(10)->create();

        $action = app(PostStockEntryAction::class);
        $entry = $action->execute(
            consumable: $consumable,
            type: StockEntryType::PURCHASE,
            quantity: 15,
            recipient: null,
            user: $user,
            remarks: 'Received fresh delivery from supplier'
        );

        $this->assertInstanceOf(Entry::class, $entry);
        $this->assertEquals(StockEntryType::PURCHASE, $entry->type);
        $this->assertEquals(15, $entry->quantity);
        $this->assertEquals(25, $entry->stock_after);
        $this->assertEquals($user->id, $entry->recorded_by);
        $this->assertNull($entry->recipient_official_id);
        $this->assertEquals('Received fresh delivery from supplier', $entry->remarks);

        $freshConsumable = $consumable->fresh();
        $this->assertEquals(25, $freshConsumable->in_stock);

        $this->assertDatabaseHas('entries', [
            'id' => $entry->id,
            'consumable_id' => $consumable->id,
            'type' => 'purchase',
            'quantity' => 15,
            'stock_after' => 25,
            'recorded_by' => $user->id,
        ]);
    }

    /**
     * Test issue entry decrements consumable stock and records accurate ledger stock_after.
     */
    public function test_issue_entry_decrements_stock_and_records_correct_stock_after(): void
    {
        $user = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $official = Official::factory()->create(['name' => 'Alice Engineer']);
        $consumable = Consumable::factory()->inStock(25)->create();

        $action = app(PostStockEntryAction::class);
        $entry = $action->execute(
            consumable: $consumable,
            type: StockEntryType::ISSUE,
            quantity: 8,
            recipient: $official,
            user: $user,
            remarks: 'Issued for Q3 project stationery'
        );

        $this->assertInstanceOf(Entry::class, $entry);
        $this->assertEquals(StockEntryType::ISSUE, $entry->type);
        $this->assertEquals(8, $entry->quantity);
        $this->assertEquals(17, $entry->stock_after);
        $this->assertEquals($official->id, $entry->recipient_official_id);
        $this->assertEquals($user->id, $entry->recorded_by);

        $freshConsumable = $consumable->fresh();
        $this->assertEquals(17, $freshConsumable->in_stock);

        $this->assertDatabaseHas('entries', [
            'id' => $entry->id,
            'consumable_id' => $consumable->id,
            'type' => 'issue',
            'quantity' => 8,
            'stock_after' => 17,
            'recipient_official_id' => $official->id,
        ]);
    }

    /**
     * Test insufficient stock throws InsufficientStockException and rolls back transaction.
     */
    public function test_insufficient_stock_throws_exception_and_leaves_balance_untouched(): void
    {
        $user = User::factory()->create(['role' => UserRole::STOCK_OPERATOR]);
        $official = Official::factory()->create();
        $consumable = Consumable::factory()->inStock(5)->create(['name' => 'Toner Cartridge']);

        $action = app(PostStockEntryAction::class);

        try {
            $action->execute(
                consumable: $consumable,
                type: StockEntryType::ISSUE,
                quantity: 10,
                recipient: $official,
                user: $user
            );
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException $e) {
            $this->assertEquals(10, $e->requested);
            $this->assertEquals(5, $e->available);
            $this->assertEquals($consumable->id, $e->consumable->id);
            $this->assertStringContainsString('Insufficient stock for', $e->getMessage());
        }

        // Verify stock remains untouched
        $this->assertEquals(5, $consumable->fresh()->in_stock);

        // Verify no entry was recorded
        $this->assertDatabaseMissing('entries', [
            'consumable_id' => $consumable->id,
        ]);
    }

    /**
     * Test idempotency key prevents duplicate transactions and re-mutation.
     */
    public function test_idempotency_key_prevents_duplicate_stock_mutations(): void
    {
        $user = User::factory()->create(['role' => UserRole::STOCK_OPERATOR]);
        $consumable = Consumable::factory()->inStock(10)->create();
        $idempotencyKey = 'txn-req-' . uniqid();

        $action = app(PostStockEntryAction::class);

        // First execution
        $firstEntry = $action->execute(
            consumable: $consumable,
            type: StockEntryType::PURCHASE,
            quantity: 10,
            recipient: null,
            user: $user,
            remarks: 'First post',
            idempotencyKey: $idempotencyKey
        );

        $this->assertEquals(20, $consumable->fresh()->in_stock);
        $this->assertEquals(20, $firstEntry->stock_after);

        // Duplicate execution with the same idempotency key
        $secondEntry = $action->execute(
            consumable: $consumable,
            type: StockEntryType::PURCHASE,
            quantity: 10,
            recipient: null,
            user: $user,
            remarks: 'Duplicate replay attempt',
            idempotencyKey: $idempotencyKey
        );

        // Must return the exact same entry without double-incrementing stock
        $this->assertEquals($firstEntry->id, $secondEntry->id);
        $this->assertEquals(20, $consumable->fresh()->in_stock, 'Stock balance should remain 20, not double-increment to 30.');

        $this->assertCount(1, Entry::where('idempotency_key', $idempotencyKey)->get());
    }

    /**
     * Test inbound and outbound inventory adjustments.
     */
    public function test_adjustment_in_and_adjustment_out_mutations(): void
    {
        $user = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $consumable = Consumable::factory()->inStock(20)->create();

        $action = app(PostStockEntryAction::class);

        // Adjustment In (+5)
        $adjIn = $action->execute(
            consumable: $consumable,
            type: StockEntryType::ADJUSTMENT_IN,
            quantity: 5,
            recipient: null,
            user: $user,
            remarks: 'Audit found surplus'
        );

        $this->assertEquals(25, $adjIn->stock_after);
        $this->assertEquals(25, $consumable->fresh()->in_stock);
        $this->assertEquals(StockEntryType::ADJUSTMENT_IN, $adjIn->type);

        // Adjustment Out (-7)
        $adjOut = $action->execute(
            consumable: $consumable,
            type: StockEntryType::ADJUSTMENT_OUT,
            quantity: 7,
            recipient: null,
            user: $user,
            remarks: 'Damaged during water leak'
        );

        $this->assertEquals(18, $adjOut->stock_after);
        $this->assertEquals(18, $consumable->fresh()->in_stock);
        $this->assertEquals(StockEntryType::ADJUSTMENT_OUT, $adjOut->type);
    }

    /**
     * Test zero or negative quantity throws InvalidArgumentException.
     */
    public function test_zero_or_negative_quantity_throws_invalid_argument_exception(): void
    {
        $user = User::factory()->create();
        $consumable = Consumable::factory()->inStock(10)->create();
        $action = app(PostStockEntryAction::class);

        $this->expectException(InvalidArgumentException::class);
        $action->execute(
            consumable: $consumable,
            type: StockEntryType::PURCHASE,
            quantity: 0,
            recipient: null,
            user: $user
        );
    }

    /**
     * Test negative quantity validation.
     */
    public function test_negative_quantity_throws_invalid_argument_exception(): void
    {
        $user = User::factory()->create();
        $consumable = Consumable::factory()->inStock(10)->create();
        $action = app(PostStockEntryAction::class);

        $this->expectException(InvalidArgumentException::class);
        $action->execute(
            consumable: $consumable,
            type: StockEntryType::PURCHASE,
            quantity: -5,
            recipient: null,
            user: $user
        );
    }

    /**
     * Test low stock query scope and helper method.
     */
    public function test_low_stock_scope_and_helper_filters_items_correctly(): void
    {
        $low = Consumable::factory()->create([
            'name' => 'Low Stock Item',
            'in_stock' => 5,
            'min_quantity' => 10,
        ]);

        $exact = Consumable::factory()->create([
            'name' => 'Exact Threshold Item',
            'in_stock' => 10,
            'min_quantity' => 10,
        ]);

        $healthy = Consumable::factory()->create([
            'name' => 'Healthy Stock Item',
            'in_stock' => 25,
            'min_quantity' => 10,
        ]);

        $untracked = Consumable::factory()->create([
            'name' => 'Untracked Item',
            'in_stock' => 2,
            'min_quantity' => null,
        ]);

        // Test helper method
        $this->assertTrue($low->isLowStock());
        $this->assertTrue($exact->isLowStock());
        $this->assertFalse($healthy->isLowStock());
        $this->assertFalse($untracked->isLowStock());

        // Test query scope
        $lowStockItems = Consumable::lowStock()->get();
        $this->assertTrue($lowStockItems->contains('id', $low->id));
        $this->assertTrue($lowStockItems->contains('id', $exact->id));
        $this->assertFalse($lowStockItems->contains('id', $healthy->id));
        $this->assertFalse($lowStockItems->contains('id', $untracked->id));
    }
}
