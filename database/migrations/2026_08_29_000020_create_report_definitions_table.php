<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('report_definitions')) {
            Schema::create('report_definitions', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 100);
                $table->string('category', 50); // REGISTER, MIS, PERIODIC, VERIFICATION, AUDIT
                $table->text('description')->nullable();
                $table->string('generator_class', 255);
                $table->string('default_format', 20)->default('html');
                $table->json('allowed_formats')->nullable();
                $table->boolean('is_certifiable')->default(false);
                $table->boolean('supports_as_on_date')->default(true);
                $table->boolean('supports_period')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('category');
                $table->index('is_active');
            });

            $this->seedStandardDefinitions();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_definitions');
    }

    private function seedStandardDefinitions(): void
    {
        $now = now();
        $definitions = [
            [
                'code' => 'INV-ASSET-REGISTER',
                'name' => 'Fixed Asset Register (Live & As-on-Date)',
                'category' => 'REGISTER',
                'description' => 'Comprehensive register of all physical & IT capital assets under organizational custody.',
                'generator_class' => 'App\\Services\\Reporting\\Generators\\AssetRegisterGenerator',
                'default_format' => 'html',
                'allowed_formats' => json_encode(['html', 'xlsx', 'pdf', 'csv']),
                'is_certifiable' => true,
                'supports_as_on_date' => true,
                'supports_period' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'INV-CONSUMABLE-REGISTER',
                'name' => 'Consumable Stock Register',
                'category' => 'REGISTER',
                'description' => 'Immutable stock ledger balance and movement register for consumables.',
                'generator_class' => 'App\\Services\\Reporting\\Generators\\ConsumableRegisterGenerator',
                'default_format' => 'html',
                'allowed_formats' => json_encode(['html', 'xlsx', 'pdf', 'csv']),
                'is_certifiable' => true,
                'supports_as_on_date' => true,
                'supports_period' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'INV-AMC-EXPIRY',
                'name' => 'Warranty & AMC Expiry Schedule',
                'category' => 'MIS',
                'description' => 'Schedule of equipment with expiring warranty, AMC, or end-of-support dates.',
                'generator_class' => 'App\\Services\\Reporting\\Generators\\AmcExpiryReportGenerator',
                'default_format' => 'html',
                'allowed_formats' => json_encode(['html', 'xlsx', 'csv']),
                'is_certifiable' => false,
                'supports_as_on_date' => true,
                'supports_period' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'INV-OFFICE-SUMMARY',
                'name' => 'Hierarchical Office Summary & Rollup',
                'category' => 'MIS',
                'description' => 'Consolidated multi-unit inventory metrics with drilldown for higher offices.',
                'generator_class' => 'App\\Services\\Reporting\\Generators\\OfficeSummaryReportGenerator',
                'default_format' => 'html',
                'allowed_formats' => json_encode(['html', 'xlsx']),
                'is_certifiable' => false,
                'supports_as_on_date' => true,
                'supports_period' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'INV-DATA-QUALITY',
                'name' => 'Inventory Data Quality & Completeness Position',
                'category' => 'MIS',
                'description' => 'Data completeness metrics across tag, serial, location, and verification compliance.',
                'generator_class' => 'App\\Services\\Reporting\\Generators\\DataQualityReportGenerator',
                'default_format' => 'html',
                'allowed_formats' => json_encode(['html', 'xlsx']),
                'is_certifiable' => false,
                'supports_as_on_date' => true,
                'supports_period' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('report_definitions')->insert($definitions);
    }
};
