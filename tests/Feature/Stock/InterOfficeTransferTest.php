<?php

namespace Tests\Feature\Stock;

use App\Enums\LocationType;
use App\Enums\OrganizationalUnitType;
use App\Enums\UserRole;
use App\Exceptions\InsufficientStockException;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\Stock\LocationStockService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterOfficeTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_requires_source_write_and_destination_acceptance(): void
    {
        [$sourceUnit, $source] = $this->office('NDC');
        [$destinationUnit, $destination] = $this->office('RO-DL');
        $sourceOperator = $this->operatorFor($sourceUnit);
        $destinationOperator = $this->operatorFor($destinationUnit);
        $consumable = Consumable::factory()->create(['in_stock' => 0]);
        $service = app(LocationStockService::class);
        $service->purchase($consumable, $source, 10, $sourceOperator, 'source-opening');

        $transfer = $service->transfer(
            $consumable,
            $source,
            $destination,
            4,
            $sourceOperator,
            $destinationOperator,
            'ndc-to-ro'
        );

        $this->assertSame(6, StockBalance::where('location_id', $source->id)->value('quantity'));
        $this->assertSame(4, StockBalance::where('location_id', $destination->id)->value('quantity'));
        $this->assertSame(10, $consumable->fresh()->in_stock, 'A transfer must not change organization-wide stock.');
        $this->assertSame($sourceOperator->id, $transfer->recorded_by);
        $this->assertSame($destinationOperator->id, $transfer->accepted_by);
    }

    public function test_source_operator_cannot_self_accept_at_an_unrelated_office(): void
    {
        [$sourceUnit, $source] = $this->office('NDC');
        [, $destination] = $this->office('RO-DL');
        $sourceOperator = $this->operatorFor($sourceUnit);
        $consumable = Consumable::factory()->create(['in_stock' => 0]);
        $service = app(LocationStockService::class);
        $service->purchase($consumable, $source, 5, $sourceOperator, 'opening');

        $this->expectException(AuthorizationException::class);
        $service->transfer(
            $consumable,
            $source,
            $destination,
            2,
            $sourceOperator,
            $sourceOperator,
            'unauthorized-acceptance'
        );
    }

    public function test_cross_office_balances_and_transactions_are_isolated(): void
    {
        [$ndcUnit, $ndcStore] = $this->office('NDC');
        [$regionalUnit, $regionalStore] = $this->office('RO-DL');
        $ndcOperator = $this->operatorFor($ndcUnit);
        $regionalOperator = $this->operatorFor($regionalUnit);
        $consumable = Consumable::factory()->create(['in_stock' => 0]);
        $service = app(LocationStockService::class);
        $service->purchase($consumable, $ndcStore, 8, $ndcOperator, 'ndc-opening');
        $service->purchase($consumable, $regionalStore, 3, $regionalOperator, 'regional-opening');

        $this->assertEquals([$ndcStore->id], StockBalance::visibleTo($ndcOperator)->pluck('location_id')->all());
        $this->assertEquals([$regionalStore->id], StockBalance::visibleTo($regionalOperator)->pluck('location_id')->all());
        $this->assertSame(1, StockTransaction::visibleTo($ndcOperator)->count());
        $this->assertSame(1, StockTransaction::visibleTo($regionalOperator)->count());
    }

    public function test_replayed_transfer_and_competing_issues_cannot_double_spend_stock(): void
    {
        [$sourceUnit, $source] = $this->office('NDC');
        [$destinationUnit, $destination] = $this->office('RO-DL');
        $sourceOperator = $this->operatorFor($sourceUnit);
        $destinationOperator = $this->operatorFor($destinationUnit);
        $consumable = Consumable::factory()->create(['in_stock' => 0]);
        $service = app(LocationStockService::class);
        $service->purchase($consumable, $source, 10, $sourceOperator, 'opening');

        $first = $service->transfer($consumable, $source, $destination, 3, $sourceOperator, $destinationOperator, 'transfer-replay');
        $replay = $service->transfer($consumable, $source, $destination, 3, $sourceOperator, $destinationOperator, 'transfer-replay');
        $this->assertTrue($first->is($replay));
        $this->assertSame(7, StockBalance::where('location_id', $source->id)->value('quantity'));
        $this->assertSame(3, StockBalance::where('location_id', $destination->id)->value('quantity'));

        $service->issue($consumable, $source, 5, $sourceOperator, 'candidate-a');
        try {
            $service->issue($consumable, $source, 5, $sourceOperator, 'candidate-b');
            $this->fail('The second competing issue should observe the locked balance and fail.');
        } catch (InsufficientStockException) {
            $this->assertSame(2, StockBalance::where('location_id', $source->id)->value('quantity'));
            $this->assertDatabaseMissing('stock_transactions', ['idempotency_key' => 'candidate-b']);
        }
    }

    /** @return array{OrganizationalUnit, Location} */
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

        return [$unit, $store];
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
