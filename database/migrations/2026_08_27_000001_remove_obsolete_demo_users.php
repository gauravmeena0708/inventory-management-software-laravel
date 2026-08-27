<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const EMAILS = [
        'north-zone-demo@example.test',
        'delhi.regional.officer@example.test',
    ];

    /**
     * Remove obsolete demo logins while retaining their operational history.
     */
    public function up(): void
    {
        $demoUserIds = DB::table('users')
            ->whereIn('email', self::EMAILS)
            ->pluck('id');

        if ($demoUserIds->isEmpty()) {
            return;
        }

        $replacementUserId = DB::table('users')
            ->where('email', 'admin@inventory.local')
            ->value('id');

        $references = [
            'developers' => ['reporting_id'],
            'attachments' => ['uploaded_by'],
            'asset_assignments' => ['assigned_by', 'return_recorded_by'],
            'payments' => ['completed_by'],
            'entries' => ['recorded_by'],
            'tasks' => ['assigned_to'],
            'spatial_maps' => ['uploaded_by'],
            'asset_placements' => ['placed_by'],
            'stock_transactions' => ['recorded_by', 'accepted_by'],
        ];

        foreach ($references as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    DB::table($table)
                        ->whereIn($column, $demoUserIds)
                        ->update([$column => $replacementUserId]);
                }
            }
        }

        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')
                ->where('causer_type', App\Models\User::class)
                ->whereIn('causer_id', $demoUserIds)
                ->update(['causer_id' => $replacementUserId]);
        }

        DB::table('users')->whereIn('id', $demoUserIds)->delete();
    }

    /**
     * Removed demo credentials are intentionally not recreated on rollback.
     */
    public function down(): void
    {
        // Irreversible data cleanup.
    }
};
