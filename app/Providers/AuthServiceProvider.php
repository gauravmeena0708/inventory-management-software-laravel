<?php

namespace App\Providers;

use App\Models\Agreement;
use App\Models\Consumable;
use App\Models\Developer;
use App\Models\Official;
use App\Models\Payment;
use App\Models\Task;
use App\Models\User;
use App\Policies\AgreementPolicy;
use App\Policies\AssetPolicy;
use App\Policies\ConsumablePolicy;
use App\Policies\DeveloperPolicy;
use App\Policies\OfficialPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        Agreement::class => AgreementPolicy::class,
        Consumable::class => ConsumablePolicy::class,
        Payment::class => PaymentPolicy::class,
        Official::class => OfficialPolicy::class,
        Developer::class => DeveloperPolicy::class,
        Task::class => TaskPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
