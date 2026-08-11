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
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('type')->default('prospect'); // prospect, partenaire, dossier_formation, custom
            $table->string('statut')->default('active'); // draft, active, completed, cancelled
            $table->json('etapes')->nullable(); // Configuration des étapes du workflow
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('type');
            $table->index('statut');
            $table->index('created_by');
        });

        // workflow_steps was already created in 2026_06_28_070806_create_workflow_steps_table.php
        // with a different schema. We recreate it here with the correct schema.
        if (Schema::hasTable('workflow_instances') && Schema::hasColumn('workflow_instances', 'current_step_id')) {
            Schema::table('workflow_instances', function (Blueprint $table) {
                $table->dropForeign(['current_step_id']);
            });

            DB::table('workflow_instances')->whereNotNull('current_step_id')->update(['current_step_id' => null]);
        }

        if (Schema::hasTable('workflow_histories')) {
            Schema::table('workflow_histories', function (Blueprint $table) {
                if (Schema::hasColumn('workflow_histories', 'from_step_id')) {
                    $table->dropForeign(['from_step_id']);
                }
                if (Schema::hasColumn('workflow_histories', 'to_step_id')) {
                    $table->dropForeign(['to_step_id']);
                }
            });

            DB::table('workflow_histories')->whereNotNull('from_step_id')->update(['from_step_id' => null]);
            DB::table('workflow_histories')->whereNotNull('to_step_id')->update(['to_step_id' => null]);
        }

        Schema::dropIfExists('workflow_steps');
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->integer('ordre')->default(0);
            $table->string('type_action')->default('approval'); // approval, notification, task
            $table->foreignId('assigne_a')->nullable()->constrained('users')->onDelete('set null');
            $table->json('conditions')->nullable(); // Conditions pour passer à l'étape suivante
            $table->timestamps();

            $table->index('workflow_id');
            $table->index('ordre');
        });

        if (Schema::hasTable('workflow_instances') && Schema::hasColumn('workflow_instances', 'current_step_id')) {
            Schema::table('workflow_instances', function (Blueprint $table) {
                $table->foreign('current_step_id')->references('id')->on('workflow_steps')->nullOnDelete();
            });
        }

        if (Schema::hasTable('workflow_histories')) {
            Schema::table('workflow_histories', function (Blueprint $table) {
                if (Schema::hasColumn('workflow_histories', 'from_step_id')) {
                    $table->foreign('from_step_id')->references('id')->on('workflow_steps')->nullOnDelete();
                }
                if (Schema::hasColumn('workflow_histories', 'to_step_id')) {
                    $table->foreign('to_step_id')->references('id')->on('workflow_steps')->nullOnDelete();
                }
            });
        }

        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->onDelete('cascade');
            $table->foreignId('workflow_step_id')->constrained('workflow_steps')->onDelete('cascade');
            $table->foreignId('entity_id')->nullable(); // ID de l'entité (prospect, partenaire, etc.)
            $table->string('entity_type')->nullable(); // Type de l'entité
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('statut')->default('pending'); // pending, approved, rejected
            $table->text('commentaire')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('workflow_id');
            $table->index('workflow_step_id');
            $table->index(['entity_id', 'entity_type']);
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflows');
    }
};
