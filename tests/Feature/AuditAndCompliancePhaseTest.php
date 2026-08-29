<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\AuditResponseType;
use App\Enums\AuditStatus;
use App\Enums\AuditType;
use App\Enums\ObservationSeverity;
use App\Enums\ObservationStatus;
use App\Enums\OrganizationalUnitType;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AuditEngagement;
use App\Models\AuditObservation;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\Audit\AtrReportGenerator;
use App\Services\Audit\AuditAgingMetricsService;
use App\Services\Audit\CreateAuditEngagementAction;
use App\Services\Audit\IssueAuditObservationAction;
use App\Services\Audit\SettleAuditObservationAction;
use App\Services\Audit\SubmitAuditResponseAction;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditAndCompliancePhaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $auditor;
    private User $manager;
    private OrganizationalUnit $auditedUnit;
    private OrganizationalUnit $auditWingUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->auditedUnit = OrganizationalUnit::create([
            'code' => 'RO-DELHI',
            'name' => 'Regional Office Delhi',
            'unit_type' => OrganizationalUnitType::REGIONAL_OFFICE,
            'is_active' => true,
        ]);

        $this->auditWingUnit = OrganizationalUnit::create([
            'code' => 'HQ-AUDIT',
            'name' => 'HQ Internal Audit Wing',
            'unit_type' => OrganizationalUnitType::ROOT,
            'is_active' => true,
        ]);

        $this->auditor = User::factory()->create(['role' => UserRole::AUDITOR]);
        $this->auditor->organizationalUnits()->attach($this->auditWingUnit->id, [
            'read_scope' => 'descendants',
            'write_scope' => 'descendants',
        ]);

        $this->manager = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $this->manager->organizationalUnits()->attach($this->auditedUnit->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);

        app(OrganizationalContext::class)->setDefaultUnit($this->auditor, $this->auditWingUnit->id);
        app(OrganizationalContext::class)->setDefaultUnit($this->manager, $this->auditedUnit->id);
    }

    public function test_complete_audit_engagement_observation_and_settlement_lifecycle(): void
    {
        $asset = Asset::create([
            'name' => 'High-End Database Server',
            'asset_tag' => 'TAG-SRV-999',
            'asset_type' => AssetType::SERVER,
            'purchase_cost' => 500000.00,
            'organizational_unit_id' => $this->auditedUnit->id,
            'status' => AssetStatus::IN_USE,
        ]);

        // 1. Create audit engagement
        /** @var CreateAuditEngagementAction $createEngagement */
        $createEngagement = app(CreateAuditEngagementAction::class);
        $engagement = $createEngagement->execute([
            'audit_type' => AuditType::INVENTORY_AUDIT,
            'audited_organizational_unit_id' => $this->auditedUnit->id,
            'auditing_organizational_unit_id' => $this->auditWingUnit->id,
            'scope_text' => 'Physical verification and log reconciliation of server assets.',
        ], $this->auditor);

        $this->assertEquals(AuditStatus::IN_PROGRESS, $engagement->status);

        // 2. Issue audit observation
        /** @var IssueAuditObservationAction $issueAction */
        $issueAction = app(IssueAuditObservationAction::class);
        $observation = $issueAction->execute($engagement, [
            'para_number' => 'Para 1.1',
            'title' => 'Missing AMC coverage for mission-critical database server',
            'finding' => 'Server TAG-SRV-999 is operating without active OEM support agreement.',
            'severity' => ObservationSeverity::HIGH,
            'financial_implication' => 75000.00,
            'asset_ids' => [$asset->id],
        ], $this->auditor);

        $this->assertEquals(ObservationStatus::ISSUED, $observation->status);
        $this->assertEquals(1, $observation->assets()->count());

        // 3. Department submits official reply
        /** @var SubmitAuditResponseAction $replyAction */
        $replyAction = app(SubmitAuditResponseAction::class);
        $replyAction->execute($observation, [
            'response_type' => AuditResponseType::OFFICE_REPLY,
            'body' => 'GeM procurement for 3-year Comprehensive AMC initiated under Order #GEMC-88123.',
        ], $this->manager);

        $this->assertEquals(ObservationStatus::REPLY_RECEIVED, $observation->fresh()->status);

        // 4. Auditor examines and settles observation
        /** @var SettleAuditObservationAction $settleAction */
        $settleAction = app(SettleAuditObservationAction::class);
        $settledObservation = $settleAction->execute($observation, [
            'remarks' => 'Verified GeM Sanction Order #GEMC-88123. Para settled.',
        ], $this->auditor);

        $this->assertEquals(ObservationStatus::SETTLED, $settledObservation->status);
        $this->assertNotNull($settledObservation->closed_at);
        $this->assertEquals(AuditStatus::CLOSED, $engagement->fresh()->status);

        // 5. Generate ATR report
        /** @var AtrReportGenerator $atrGenerator */
        $atrGenerator = app(AtrReportGenerator::class);
        $atr = $atrGenerator->generate($engagement);

        $this->assertEquals(1, $atr['summary']['total_paras']);
        $this->assertEquals(1, $atr['summary']['settled_paras']);
        $this->assertEquals(0, $atr['summary']['outstanding_paras']);
        $this->assertEquals(100, $atr['summary']['compliance_percentage']);
    }

    public function test_audit_aging_metrics_service_buckets(): void
    {
        /** @var CreateAuditEngagementAction $createEngagement */
        $createEngagement = app(CreateAuditEngagementAction::class);
        $engagement = $createEngagement->execute([
            'audit_type' => AuditType::INTERNAL_AUDIT,
            'audited_organizational_unit_id' => $this->auditedUnit->id,
        ], $this->auditor);

        /** @var IssueAuditObservationAction $issueAction */
        $issueAction = app(IssueAuditObservationAction::class);

        // Recent observation
        $issueAction->execute($engagement, [
            'para_number' => 'Para 1.1',
            'title' => 'Recent observation',
            'finding' => 'Finding text',
            'severity' => ObservationSeverity::CRITICAL,
            'financial_implication' => 120000.00,
        ], $this->auditor);

        // Older observation
        $oldObs = $issueAction->execute($engagement, [
            'para_number' => 'Para 1.2',
            'title' => 'Older observation',
            'finding' => 'Finding text',
            'severity' => ObservationSeverity::HIGH,
            'financial_implication' => 80000.00,
        ], $this->auditor);
        $oldObs->update(['issued_at' => now()->subDays(45)]);

        /** @var AuditAgingMetricsService $agingService */
        $agingService = app(AuditAgingMetricsService::class);
        $position = $agingService->getAgingPosition($this->auditedUnit);

        $this->assertEquals(2, $position['total_open_observations']);
        $this->assertEquals(200000.00, $position['total_financial_exposure']);
        $this->assertEquals(1, $position['aging_buckets']['less_than_30_days']);
        $this->assertEquals(1, $position['aging_buckets']['30_to_90_days']);
        $this->assertEquals(1, $position['severity_breakdown']['critical']);
        $this->assertEquals(1, $position['severity_breakdown']['high']);
    }
}
