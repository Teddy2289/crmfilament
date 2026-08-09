<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_groupe_id')->constrained()->onDelete('cascade');
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            
            // Relations polymorphiques
            $table->string('instanceable_type')->nullable();
            $table->unsignedBigInteger('instanceable_id')->nullable();
            
            $table->string('statut')->default('en_cours'); // en_cours, termine, annule
            $table->dateTime('date_debut')->nullable();
            $table->dateTime('date_fin')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['instanceable_type', 'instanceable_id']);
            $table->index('workflow_groupe_id');
            $table->index('current_step_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};
