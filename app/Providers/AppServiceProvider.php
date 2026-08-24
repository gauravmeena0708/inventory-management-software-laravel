<?php

namespace App\Providers;

use App\Contracts\AttachmentStore;
use App\Contracts\AuditRecorder;
use App\Contracts\TabularExporter;
use App\Services\Audit\SpatieAuditRecorder;
use App\Services\Export\MaatwebsiteTabularExporter;
use App\Services\Storage\PrivateDiskAttachmentStore;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AttachmentStore::class, PrivateDiskAttachmentStore::class);
        $this->app->bind(AuditRecorder::class, SpatieAuditRecorder::class);
        $this->app->bind(TabularExporter::class, MaatwebsiteTabularExporter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
