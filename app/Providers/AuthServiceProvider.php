<?php

namespace App\Providers;

use App\Models\Acquisition;
use App\Models\Agreement;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDisposal;
use App\Models\AssetTransfer;
use App\Models\Attachment;
use App\Models\AuditEngagement;
use App\Models\AuditObservation;
use App\Models\Consumable;
use App\Models\Developer;
use App\Models\FileRecord;
use App\Models\InventoryVerification;
use App\Models\Location;
use App\Models\MaintenanceTicket;
use App\Models\Official;
use App\Models\Payment;
use App\Models\ReportRun;
use App\Models\SpatialMap;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Task;
use App\Models\User;
use App\Policies\AcquisitionPolicy;
use App\Policies\AgreementPolicy;
use App\Policies\AssetCategoryPolicy;
use App\Policies\AssetDisposalPolicy;
use App\Policies\AssetPolicy;
use App\Policies\AssetTransferPolicy;
use App\Policies\AttachmentPolicy;
use App\Policies\AuditEngagementPolicy;
use App\Policies\AuditObservationPolicy;
use App\Policies\ConsumablePolicy;
use App\Policies\DeveloperPolicy;
use App\Policies\FileRecordPolicy;
use App\Policies\InventoryVerificationPolicy;
use App\Policies\LocationPolicy;
use App\Policies\MaintenanceTicketPolicy;
use App\Policies\OfficialPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ReportRunPolicy;
use App\Policies\SpatialMapPolicy;
use App\Policies\StockBalancePolicy;
use App\Policies\StockTransactionPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Asset::class => AssetPolicy::class,
        AssetCategory::class => AssetCategoryPolicy::class,
        AssetTransfer::class => AssetTransferPolicy::class,
        AssetDisposal::class => AssetDisposalPolicy::class,
        MaintenanceTicket::class => MaintenanceTicketPolicy::class,
        InventoryVerification::class => InventoryVerificationPolicy::class,
        Acquisition::class => AcquisitionPolicy::class,
        ReportRun::class => ReportRunPolicy::class,
        AuditEngagement::class => AuditEngagementPolicy::class,
        AuditObservation::class => AuditObservationPolicy::class,
        Attachment::class => AttachmentPolicy::class,
        Agreement::class => AgreementPolicy::class,
        Consumable::class => ConsumablePolicy::class,
        Payment::class => PaymentPolicy::class,
        Official::class => OfficialPolicy::class,
        Developer::class => DeveloperPolicy::class,
        FileRecord::class => FileRecordPolicy::class,
        Location::class => LocationPolicy::class,
        SpatialMap::class => SpatialMapPolicy::class,
        StockBalance::class => StockBalancePolicy::class,
        StockTransaction::class => StockTransactionPolicy::class,
        Task::class => TaskPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
