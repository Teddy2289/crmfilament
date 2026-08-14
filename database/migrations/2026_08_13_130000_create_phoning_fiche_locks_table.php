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
        Schema::create('phoning_fiche_locks', function (Blueprint $table) {
            $table->id();
            $table->morphs('lockable'); // lockable_type, lockable_id
            $table->foreignId('locked_by_user_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('locked_at')->useCurrent();
            $table->dateTime('heartbeat_at')->useCurrent();
            $table->timestamps();

            // Index pour recherche rapide par utilisateur et par fiche
            $table->index(['lockable_type', 'lockable_id']);
            $table->index('locked_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phoning_fiche_locks');
    }
};
