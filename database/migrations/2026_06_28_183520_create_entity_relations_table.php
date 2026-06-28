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
        Schema::create('entity_relations', function (Blueprint $table) {
            $table->id();
            $table->string('from_type'); // 'prospect', 'client', 'partenaire'
            $table->unsignedBigInteger('from_id');
            $table->string('to_type'); // 'prospect', 'client', 'partenaire'
            $table->unsignedBigInteger('to_id');
            $table->string('relation_type')->default('related'); // 'related', 'converted_to', 'parent_of', etc.
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['from_type', 'from_id', 'to_type', 'to_id'], 'unique_relation');
            $table->index(['from_type', 'from_id']);
            $table->index(['to_type', 'to_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_relations');
    }
};
