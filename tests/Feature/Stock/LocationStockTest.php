<?php

namespace Tests\Feature\Stock;

use App\Enums\LocationType;
use App\Enums\OrganizationalUnitType;
use App\Enums\StockEntryType;
use App\Enums\StockTransactionType;
use App\Enums\UserRole;
use App\Exceptions\InsufficientStockException;
use App\Models\Consumable;
use App\Models\Entry;
use App\Models\Location;
use App\Models\Official;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\Stock\LegacyLocationStockMigrator;
use App\Services\Stock\LocationStockService;
use Database\Seeders\EpfoNdcHierarchySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class LocationStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumable_catalog_has_independent_location_balances(): void
    {
        [$unit, $site, $firstStore] = $this->office('NDC');
        $secondStore = Location::factory()->create([
            'site_id' => $site->id,
            'code' => 'NDC-SECOND-STORE',
            'location_type' => LocationType::STORE,
            'path' => '/second/',
            'is_active' => true,
        ]);
        $operator = $this->operatorFor($unit);
        $consumable = Consumable::factory()->create(['in_stock' => 0]);

        $service = app(LocationStockService::class);
        $service->purchase($consumable, $firstStore, 12, $operator, 'purchase:first');
        $service->purchase($consumable, $secondStore, 7, $operator, 'purchase:second');

        $this->assertDatabaseHas('stock_balances', [
            'consumable_id' => $consumable->id,
            'location_id' => $firstStore->id,
            'quantity' => 12,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'consumable_id' => $consumable->id,
            'location_id' => $secondStore->id,
            'quantity' => 7,
        ]);
        $this->assertSame(19, $consumable->fresh()->in_stock, 'Legacy total must remain a synchronized aggregate.');
    }

    public function test_purchase_issue_and_adjustments_create_immutable_location_ledger(): void
    {
        [$unit, , $store] = $this->office('NDC');
        $operator = $this->operatorFor($unit);
        $official = Official::factory()->create(['location_id' => $store->id]);
        $consumable = Consumable::factory()->create(['in_stock' => 0]);
        $service = app(LocationStockService::class);

        $purchase = $service->purchase($consumable, $store, 20, $operator, 'stock:purchase');
        $issue = $service->issue($consumable, $store, 4, $operator, 'stock:issue', $official);
        $increase = $service->adjust($consumable, $store, 3, $operator, 'stock:adjust-in');
        $decrease = $service->adjust($consumable, $store, -2, $operator, 'stock:adjust-out');

        $this->assertSame(StockTransactionType::PURCHASE, $purchase->transaction_type);
        $this->assertSame(StockTransactionType::ISSUE, $issue->transaction_type);
        $this->assertSame(StockTransactionType::ADJUSTMENT_IN, $increase->transaction_type);
        $this->assertSame(StockTransactionType::ADJUSTMENT_OUT, $decrease->transaction_type);
        $this->assertSame(17, StockBalance::where('consumable_id', $consumable->id)->value('quantity'));
        $this->assertSame(17, $consumable->fresh()->in_stock);
        $this->assertSame($official->id, $issue->recipient_official_id);

        $this->expectException(LogicException::class);
        $issue->update(['remarks' => 'History rewrite attempt']);
    }

    public function test_insufficient_stock_rolls_back_without_a_transaction(): void
    {
        [$unit, , $store] = $this->office('NDC');
        $operator = $this->operatorFor($unit);
        $consumable = Consumable::factory()->create(['in_stock' => 0]);
        $service = app(LocationStockService::class);
        $service->purchase($consumable, $store, 3, $operator, 'opening');

        try {
            $service->issue($consumable, $store, 4, $operator, 'too-much');
            $this->fail('Expected insufficient stock exception.');
        } catch (InsufficientStockException $exception) {
            $this->assertSame(3, $exception->available);
            $this->assertSame(4, $exception->requested);
        }

        $this->assertSame(3, StockBalance::where('consumable_id', $consumable->id)->value('quantity'));
        $this->assertDatabaseMissing('stock_transactions', ['idempotency_key' => 'too-much']);
    }

    public function test_replayed_request_is_safe_and_changed_payload_is_rejected(): void
    {
        [$unit, , $store] = $this->office('NDC');
        $operator = $this->operatorFor($unit);
        $consumable = Consumable::factory()->create(['in_stock' => 0]);
        $service = app(LocationStockService::class);

        $first = $service->purchase($consumable, $store, 5, $operator, 'replay-key');
        $replay = $service->purchase($consumable, $store, 5, $operator, 'replay-key');

        $this->assertTrue($first->is($replay));
        $this->assertSame(5, $consumable->fresh()->in_stock);
        $this->assertSame(1, StockTransaction::where('idempotency_key', 'replay-key')->count());

        $this->expectException(InvalidArgumentException::class);
        $service->purchase($consumable, $store, 6, $operator, 'replay-key');
    }

    public function test_database_enforces_one_balance_per_consumable_and_location(): void
    {
        [, , $store] = $this->office('NDC');
        $consumable = Consumable::factory()->create();
        StockBalance::create(['consumable_id' => $consumable->id, 'location_id' => $store->id]);

        $this->expectException(QueryException::class);
        StockBalance::create(['consumable_id' => $consumable->id, 'location_id' => $store->id]);
    }

    public function test_http_purchase_endpoint_posts_only_to_an_authorized_location(): void
    {
        [$unit, , $store] = $this->office('NDC');
        [, , $unrelatedStore] = $this->office('RO-DL');
        $operator = $this->operatorFor($unit);
        $consumable = Consumable::factory()->create(['in_stock' => 0]);

        $this->actingAs($operator)->postJson(route('stock.purchases.store'), [
            'consumable_id' => $consumable->id,
            'destination_location_id' => $store->id,
            'quantity' => 6,
            'idempotency_key' => 'http-purchase',
        ])->assertCreated()
            ->assertJsonPath('transaction.destination_location_id', $store->id);

        $this->actingAs($operator)->postJson(route('stock.purchases.store'), [
            'consumable_id' => $consumable->id,
            'destination_location_id' => $unrelatedStore->id,
            'quantity' => 6,
            'idempotency_key' => 'http-cross-office-purchase',
        ])->assertForbidden();

        $this->assertDatabaseMissing('stock_transactions', ['idempotency_key' => 'http-cross-office-purchase']);
    }

    public function test_legacy_migration_copies_history_reconciles_and_is_idempotent(): void
    {
        [, , $store] = $this->office('NDC');
        $consumable = Consumable::factory()->create(['in_stock' => 8, 'min_quantity' => 2, 'max_quantity' => 20]);
        $purchase = Entry::factory()->create([
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::PURCHASE,
            'quantity' => 10,
            'stock_after' => 10,
        ]);
        $issue = Entry::factory()->create([
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::ISSUE,
            'quantity' => 1,
            'stock_after' => 9,
        ]);

        $migrator = app(LegacyLocationStockMigrator::class);
        $first = $migrator->migrateTo($store);
        $second = $migrator->migrateTo($store);

        $this->assertSame(1, $first['balances_created']);
        $this->assertSame(2, $first['entries_copied']);
        $this->assertSame(-1, $first['discrepancies'][0]['difference']);
        $this->assertSame(0, $second['balances_created']);
        $this->assertSame(0, $second['entries_copied']);
        $this->assertSame(2, Entry::count(), 'The original ledger must remain unchanged.');
        $this->assertSame(2, StockTransaction::whereNotNull('legacy_entry_id')->count());
        $this->assertDatabaseHas('stock_transactions', [
            'legacy_entry_id' => $purchase->id,
            'destination_location_id' => $store->id,
            'destination_stock_after' => 10,
        ]);
        $this->assertDatabaseHas('stock_transactions', [
            'legacy_entry_id' => $issue->id,
            'source_location_id' => $store->id,
            'source_stock_after' => 9,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'consumable_id' => $consumable->id,
            'location_id' => $store->id,
            'quantity' => 8,
            'min_quantity' => 2,
            'max_quantity' => 20,
        ]);
    }

    public function test_ndc_mapping_command_migrates_and_verifies_location_stock(): void
    {
        $this->seed(EpfoNdcHierarchySeeder::class);
        $store = Location::where('code', 'NDC_MAIN_STORE')->firstOrFail();
        $consumable = Consumable::factory()->create(['in_stock' => 4]);
        $entry = Entry::factory()->create([
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::PURCHASE,
            'quantity' => 4,
            'stock_after' => 4,
        ]);

        $this->artisan('epfo:map-ndc-inventory', ['--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseMissing('stock_balances', [
            'consumable_id' => $consumable->id,
            'location_id' => $store->id,
        ]);
        $this->assertDatabaseMissing('stock_transactions', ['legacy_entry_id' => $entry->id]);
        $this->artisan('epfo:map-ndc-inventory', ['--verify' => true])->assertFailed();

        $this->artisan('epfo:map-ndc-inventory', ['--apply' => true])->assertSuccessful();

        $this->assertDatabaseHas('stock_balances', [
            'consumable_id' => $consumable->id,
            'location_id' => $store->id,
            'quantity' => 4,
        ]);
        $this->assertDatabaseHas('stock_transactions', [
            'legacy_entry_id' => $entry->id,
            'destination_location_id' => $store->id,
        ]);
        $this->artisan('epfo:map-ndc-inventory', ['--verify' => true])->assertSuccessful();
    }

    /** @return array{OrganizationalUnit, Site, Location} */
    private function office(string $code): array
    {
        $unit = OrganizationalUnit::factory()->create([
            'code' => $code,
            'unit_type' => OrganizationalUnitType::NDC,
            'path' => '/'.$code.'/',
            'is_active' => true,
        ]);
        $site = Site::create(['code' => $code.'-SITE', 'name' => $code.' Site', 'is_active' => true]);
        $site->organizationalUnits()->attach($unit->id);
        $store = Location::factory()->create([
            'site_id' => $site->id,
            'code' => $code.'-STORE',
            'location_type' => LocationType::STORE,
            'path' => '/'.$code.'-STORE/',
            'is_active' => true,
        ]);

        return [$unit, $site, $store];
    }

    private function operatorFor(OrganizationalUnit $unit): User
    {
        $user = User::factory()->create(['role' => UserRole::STOCK_OPERATOR]);
        $user->organizationalUnits()->attach($unit->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);

        return $user;
    }
}
