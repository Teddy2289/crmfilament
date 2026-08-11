<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->foreignId('parent_step_id')->nullable()->after('workflow_groupe_id')->constrained('workflow_steps')->nullOnDelete();
            $table->string('condition_label')->nullable()->after('config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropForeign(['parent_step_id']);
            $table->dropColumn(['parent_step_id', 'condition_label']);
        });
    }
};
