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
        Schema::table('prospects', function (Blueprint $table) {
            $table->boolean('mail1_envoye')->default(false)->after('statut');
            $table->timestamp('mail1_envoye_at')->nullable()->after('mail1_envoye');
            $table->boolean('mail2_envoye')->default(false)->after('mail1_envoye_at');
            $table->timestamp('mail2_envoye_at')->nullable()->after('mail2_envoye');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['mail1_envoye', 'mail1_envoye_at', 'mail2_envoye', 'mail2_envoye_at']);
        });
    }
};
