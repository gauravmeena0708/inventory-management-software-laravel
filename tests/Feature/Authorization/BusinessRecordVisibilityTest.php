<?php

namespace Tests\Feature\Authorization;

use App\Enums\ServiceExecutionChannel;
use App\Enums\UserRole;
use App\Exports\AgreementsExport;
use App\Models\Agreement;
use App\Models\Attachment;
use App\Models\FileRecord;
use App\Models\OrganizationalUnit;
use App\Models\Payment;
use App\Models\Task;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Authorization\SystemIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class BusinessRecordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_records_have_explicit_or_inherited_ownership_and_fail_closed(): void
    {
        $this->assertTrue(Schema::hasColumns('files', ['organizational_unit_id']));
        $this->assertTrue(Schema::hasColumns('agreements', ['organizational_unit_id']));
        $this->assertTrue(Schema::hasColumns('tasks', ['organizational_unit_id']));
        $this->assertFalse(Schema::hasColumn('payments', 'organizational_unit_id'));

        [$user, $local, $other] = $this->twoOfficeUser();

        $localFile = FileRecord::factory()->create(['organizational_unit_id' => $local->id]);
        $otherFile = FileRecord::factory()->create(['organizational_unit_id' => $other->id]);
        FileRecord::factory()->create(['organizational_unit_id' => null]);
        $localAgreement = Agreement::factory()->create([
            'organizational_unit_id' => $local->id,
            'file_id' => $localFile->id,
        ]);
        $otherAgreement = Agreement::factory()->create([
            'organizational_unit_id' => $other->id,
            'file_id' => $otherFile->id,
        ]);
        Agreement::factory()->create(['organizational_unit_id' => null]);
        $localPayment = Payment::factory()->create(['agreement_id' => $localAgreement->id]);
        Payment::factory()->create(['agreement_id' => $otherAgreement->id]);
        $localTask = Task::factory()->create([
            'organizational_unit_id' => $local->id,
            'file_id' => $localFile->id,
        ]);
        Task::factory()->create(['organizational_unit_id' => $other->id]);
        Task::factory()->create(['organizational_unit_id' => null]);

        $this->assertEquals([$localFile->id], FileRecord::visibleTo($user)->pluck('id')->all());
        $this->assertEquals([$localAgreement->id], Agreement::visibleTo($user)->pluck('id')->all());
        $this->assertEquals([$localPayment->id], Payment::visibleTo($user)->pluck('id')->all());
        $this->assertEquals([$localTask->id], Task::visibleTo($user)->pluck('id')->all());
    }

    public function test_list_search_dashboard_export_and_attachments_do_not_cross_offices(): void
    {
        [$user, $local, $other] = $this->twoOfficeUser();
        $user->update(['role' => UserRole::AUDITOR]);
        $localFile = FileRecord::factory()->create(['name' => 'Local file', 'organizational_unit_id' => $local->id]);
        $otherFile = FileRecord::factory()->create(['name' => 'Secret file', 'organizational_unit_id' => $other->id]);
        $localAgreement = Agreement::factory()->create([
            'name' => 'Local agreement',
            'organizational_unit_id' => $local->id,
            'file_id' => $localFile->id,
        ]);
        $otherAgreement = Agreement::factory()->create([
            'name' => 'Secret agreement',
            'organizational_unit_id' => $other->id,
            'file_id' => $otherFile->id,
        ]);
        $localPayment = Payment::factory()->create(['agreement_id' => $localAgreement->id]);
        $otherPayment = Payment::factory()->create(['agreement_id' => $otherAgreement->id]);
        $localTask = Task::factory()->create(['title' => 'Local task', 'organizational_unit_id' => $local->id]);
        $otherTask = Task::factory()->create(['title' => 'Secret task', 'organizational_unit_id' => $other->id]);
        $localAttachment = $localFile->attachments()->create($this->attachmentAttributes('local.pdf'));
        $otherAttachment = $otherAgreement->attachments()->create($this->attachmentAttributes('secret.pdf'));

        $this->actingAs($user)->getJson(route('files.index'))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $localFile->id);
        $this->actingAs($user)->getJson(route('agreements.index', ['search' => 'Secret']))
            ->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($user)->getJson(route('payments.index'))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $localPayment->id);
        $this->actingAs($user)->getJson(route('tasks.index'))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $localTask->id);

        $dashboard = $this->actingAs($user)->getJson(route('dashboard'))->assertOk();
        $dashboard->assertJsonPath('total_agreements', 1)->assertJsonPath('pending_payments', 1);

        $this->assertTrue(Attachment::visibleTo($user)->whereKey($localAttachment->id)->exists());
        $this->assertFalse(Attachment::visibleTo($user)->whereKey($otherAttachment->id)->exists());

        Carbon::setTestNow('2026-08-25 14:15:16');
        Excel::fake();
        $this->actingAs($user)->get(route('agreements.export'))->assertOk();
        Excel::assertDownloaded('agreements-2026-08-25-141516.xlsx', function (AgreementsExport $export) use ($localAgreement): bool {
            return $export->query()->pluck('agreements.id')->all() === [$localAgreement->id];
        });
        $this->assertTrue(Agreement::visibleTo($user)->whereKey($localAgreement)->exists());
        $this->assertFalse(Payment::visibleTo($user)->whereKey($otherPayment)->exists());
        $this->assertFalse(Task::visibleTo($user)->whereKey($otherTask)->exists());
    }

    public function test_unauthorized_direct_resources_consistently_return_403(): void
    {
        [$user, $local, $other] = $this->twoOfficeUser();
        $file = FileRecord::factory()->create(['organizational_unit_id' => $other->id]);
        $agreement = Agreement::factory()->create(['organizational_unit_id' => $other->id]);
        $payment = Payment::factory()->create(['agreement_id' => $agreement->id]);
        $task = Task::factory()->create(['organizational_unit_id' => $other->id]);

        $this->actingAs($user)->getJson(route('files.show', $file))->assertForbidden();
        $this->actingAs($user)->getJson(route('agreements.show', $agreement))->assertForbidden();
        $this->actingAs($user)->getJson(route('payments.show', $payment))->assertForbidden();
        $this->actingAs($user)->getJson(route('tasks.show', $task))->assertForbidden();
    }

    public function test_business_record_mutations_require_role_and_organizational_write_scope(): void
    {
        [, $local, $other] = $this->twoOfficeUser();
        $manager = User::factory()->inventoryManager()->create();
        $manager->organizationalUnits()->attach($local->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);
        $viewer = User::factory()->viewer()->create();
        $viewer->organizationalUnits()->attach($local->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);
        $localFile = FileRecord::factory()->create(['organizational_unit_id' => $local->id]);
        $otherFile = FileRecord::factory()->create(['organizational_unit_id' => $other->id]);
        $localAgreement = Agreement::factory()->create(['organizational_unit_id' => $local->id]);
        $otherAgreement = Agreement::factory()->create(['organizational_unit_id' => $other->id]);
        $localPayment = Payment::factory()->create(['agreement_id' => $localAgreement->id]);
        $localTask = Task::factory()->create(['organizational_unit_id' => $local->id]);

        $this->assertTrue($manager->can('update', $localFile));
        $this->assertTrue($manager->can('update', $localAgreement));
        $this->assertTrue($manager->can('update', $localTask));
        $this->assertFalse($manager->can('update', $otherFile));
        $this->assertFalse($manager->can('update', $otherAgreement));
        $this->assertFalse($viewer->can('update', $localTask));

        $finance = User::factory()->financeOperator()->create();
        $finance->organizationalUnits()->attach($local->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);
        $this->assertTrue($finance->can('complete', $localPayment));
        $this->assertFalse($manager->can('complete', $localPayment));
    }

    public function test_non_http_paths_use_explicit_scoped_or_organization_wide_identities(): void
    {
        [, $local, $other] = $this->twoOfficeUser();
        $localAgreement = Agreement::factory()->create(['organizational_unit_id' => $local->id]);
        $otherAgreement = Agreement::factory()->create(['organizational_unit_id' => $other->id]);
        Agreement::factory()->create(['organizational_unit_id' => null]);
        $visibility = app(OrganizationalVisibility::class);

        foreach ([
            ServiceExecutionChannel::CONSOLE,
            ServiceExecutionChannel::QUEUE,
            ServiceExecutionChannel::API,
            ServiceExecutionChannel::LEGACY_IMPORT,
        ] as $channel) {
            $identity = SystemIdentity::scoped($channel, "{$channel->value} test", $local->id, $local->id);
            $this->assertEquals(
                [$localAgreement->id],
                $visibility->applyForService(Agreement::query(), $identity)->pluck('id')->all()
            );
        }

        $organizationWide = SystemIdentity::organizationWide(
            ServiceExecutionChannel::CONSOLE,
            'Organization-wide reconciliation test'
        );
        $this->assertEqualsCanonicalizing(
            [$localAgreement->id, $otherAgreement->id],
            $visibility->applyForService(Agreement::query(), $organizationWide)->pluck('id')->all()
        );
    }

    /** @return array{User, OrganizationalUnit, OrganizationalUnit} */
    private function twoOfficeUser(): array
    {
        $local = OrganizationalUnit::factory()->create(['code' => 'LOCAL', 'path' => '/1/']);
        $other = OrganizationalUnit::factory()->create(['code' => 'OTHER', 'path' => '/2/']);
        $user = User::factory()->viewer()->create();
        $user->organizationalUnits()->attach($local->id, [
            'read_scope' => 'local',
            'write_scope' => 'none',
        ]);

        return [$user, $local, $other];
    }

    /** @return array<string, mixed> */
    private function attachmentAttributes(string $name): array
    {
        return [
            'disk' => 'private',
            'path' => "attachments/{$name}",
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'size' => 123,
            'checksum' => str_repeat('a', 64),
        ];
    }
}
