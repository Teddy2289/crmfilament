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
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_groupe_id')->constrained()->onDelete('cascade');
            $table->string('label');
            $table->string('code')->unique();
            $table->string('type')->default('task');
            $table->integer('ordre')->default(0);
            $table->json('config')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            
            $table->index(['workflow_groupe_id', 'ordre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
