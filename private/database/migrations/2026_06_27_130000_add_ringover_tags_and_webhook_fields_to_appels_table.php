<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            if (! Schema::hasColumn('appels', 'ringover_tags')) {
                $table->json('ringover_tags')->nullable()->after('ringover_agent_nom');
            }

            if (! Schema::hasColumn('appels', 'ringover_department_tag')) {
                $table->string('ringover_department_tag', 20)->nullable()->after('ringover_tags')->index();
            }

            if (! Schema::hasColumn('appels', 'ringover_status_tag')) {
                $table->string('ringover_status_tag', 50)->nullable()->after('ringover_department_tag')->index();
            }

            if (! Schema::hasColumn('appels', 'ringover_tag_validation')) {
                $table->json('ringover_tag_validation')->nullable()->after('ringover_status_tag');
            }

            if (! Schema::hasColumn('appels', 'ringover_tag_is_complete')) {
                $table->boolean('ringover_tag_is_complete')->default(false)->after('ringover_tag_validation')->index();
            }

            if (! Schema::hasColumn('appels', 'ringover_payload')) {
                $table->json('ringover_payload')->nullable()->after('ringover_tag_is_complete');
            }

            if (! Schema::hasColumn('appels', 'ringover_synced_at')) {
                $table->timestamp('ringover_synced_at')->nullable()->after('ringover_payload');
            }

            if (! Schema::hasColumn('appels', 'ringover_webhook_received_at')) {
                $table->timestamp('ringover_webhook_received_at')->nullable()->after('ringover_synced_at')->index();
            }

            if (! Schema::hasColumn('appels', 'ringover_sync_source')) {
                $table->string('ringover_sync_source', 40)->nullable()->after('ringover_webhook_received_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $columns = [
                'ringover_tags',
                'ringover_department_tag',
                'ringover_status_tag',
                'ringover_tag_validation',
                'ringover_tag_is_complete',
                'ringover_payload',
                'ringover_synced_at',
                'ringover_webhook_received_at',
                'ringover_sync_source',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('appels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
