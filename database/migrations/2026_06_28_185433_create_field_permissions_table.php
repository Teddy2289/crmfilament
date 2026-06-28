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
        Schema::create('field_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role'); // super_admin, administrateur, commercial, etc.
            $table->string('resource'); // prospects, clients, partenaires
            $table->string('field_name'); // nom, email, telephone, etc.
            $table->boolean('visible_list')->default(true);
            $table->boolean('visible_view')->default(true);
            $table->boolean('visible_edit')->default(true);
            $table->boolean('read_only')->default(false);
            $table->timestamps();

            $table->unique(['role', 'resource', 'field_name']);
            $table->index(['role', 'resource']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_permissions');
    }
};
