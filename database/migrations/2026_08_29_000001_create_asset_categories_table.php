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
        if (! Schema::hasTable('asset_categories')) {
            Schema::create('asset_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 100);
                $table->foreignId('parent_id')->nullable()->constrained('asset_categories')->nullOnDelete();
                $table->string('broad_family', 50)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('parent_id');
                $table->index('broad_family');
                $table->index('is_active');
            });
        }

        if (Schema::hasTable('assets') && ! Schema::hasColumn('assets', 'asset_category_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->foreignId('asset_category_id')->nullable()->after('asset_type')->constrained('asset_categories')->nullOnDelete();
                $table->index('asset_category_id');
                $table->index(['asset_category_id', 'status']);
            });
        }

        $this->seedStandardCategoriesAndBackfill();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'asset_category_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropForeign(['asset_category_id']);
                $table->dropIndex(['asset_category_id', 'status']);
                $table->dropIndex(['asset_category_id']);
                $table->dropColumn('asset_category_id');
            });
        }

        Schema::dropIfExists('asset_categories');
    }

    private function seedStandardCategoriesAndBackfill(): void
    {
        $families = [
            'COMPUTE' => [
                ['code' => 'DESKTOP', 'name' => 'Desktop', 'type_match' => 'desktop'],
                ['code' => 'LAPTOP', 'name' => 'Laptop', 'type_match' => 'laptop'],
                ['code' => 'WORKSTATION', 'name' => 'Workstation', 'type_match' => null],
                ['code' => 'SERVER', 'name' => 'Server', 'type_match' => 'server'],
            ],
            'NETWORK' => [
                ['code' => 'SWITCH', 'name' => 'Network Switch', 'type_match' => null],
                ['code' => 'ROUTER', 'name' => 'Router', 'type_match' => null],
                ['code' => 'FIREWALL', 'name' => 'Firewall', 'type_match' => null],
                ['code' => 'ACCESS_POINT', 'name' => 'Wireless Access Point', 'type_match' => null],
            ],
            'STORAGE' => [
                ['code' => 'STORAGE_SAN', 'name' => 'SAN Storage', 'type_match' => 'storage'],
                ['code' => 'STORAGE_NAS', 'name' => 'NAS Storage', 'type_match' => null],
            ],
            'POWER' => [
                ['code' => 'UPS', 'name' => 'UPS / Backup Power', 'type_match' => null],
                ['code' => 'PDU', 'name' => 'Power Distribution Unit', 'type_match' => null],
            ],
            'PERIPHERALS' => [
                ['code' => 'PRINTER', 'name' => 'Printer', 'type_match' => null],
                ['code' => 'SCANNER', 'name' => 'Scanner', 'type_match' => null],
                ['code' => 'MONITOR', 'name' => 'Display Monitor', 'type_match' => null],
                ['code' => 'BIOMETRIC', 'name' => 'Biometric Device', 'type_match' => null],
                ['code' => 'CCTV', 'name' => 'CCTV Equipment', 'type_match' => null],
            ],
            'OTHER' => [
                ['code' => 'DEVICE_OTHER', 'name' => 'Other IT Device', 'type_match' => 'device'],
                ['code' => 'GENERAL_OTHER', 'name' => 'Other / General Asset', 'type_match' => 'other'],
            ],
        ];

        $now = now();
        $typeToCategoryId = [];

        foreach ($families as $familyName => $categories) {
            $parent = DB::table('asset_categories')->where('code', $familyName)->first();
            if (! $parent) {
                $parentId = DB::table('asset_categories')->insertGetId([
                    'code' => $familyName,
                    'name' => ucwords(strtolower($familyName)),
                    'parent_id' => null,
                    'broad_family' => $familyName,
                    'description' => "{$familyName} broad family",
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $parentId = $parent->id;
            }

            foreach ($categories as $cat) {
                $existing = DB::table('asset_categories')->where('code', $cat['code'])->first();
                if (! $existing) {
                    $catId = DB::table('asset_categories')->insertGetId([
                        'code' => $cat['code'],
                        'name' => $cat['name'],
                        'parent_id' => $parentId,
                        'broad_family' => $familyName,
                        'description' => "{$cat['name']} asset category",
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $catId = $existing->id;
                }

                if ($cat['type_match']) {
                    $typeToCategoryId[$cat['type_match']] = $catId;
                }
            }
        }

        foreach ($typeToCategoryId as $assetType => $categoryId) {
            DB::table('assets')
                ->where('asset_type', $assetType)
                ->whereNull('asset_category_id')
                ->update(['asset_category_id' => $categoryId]);
        }
    }
};
