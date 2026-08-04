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
        Schema::create('historique_modifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('champ')->nullable()->comment('Champ modifié ou null pour création/suppression');
            $table->json('ancienne_valeur')->nullable()->comment('Valeur avant modification');
            $table->json('nouvelle_valeur')->nullable()->comment('Valeur après modification');
            $table->enum('type_modification', ['creation', 'modification', 'suppression', 'restauration'])
                ->default('modification')
                ->comment('Type de modification');
            $table->timestamp('date_modification')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('type_modification');
            $table->index('champ');
            $table->index('date_modification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historique_modifications');
    }
};
