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
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('priorite')->default('normale')->after('statut');
            $table->boolean('rappel_envoye')->default(false)->after('date_realisation');
            $table->timestamp('date_rappel')->nullable()->after('rappel_envoye');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['priorite', 'rappel_envoye', 'date_rappel']);
        });
    }
};
