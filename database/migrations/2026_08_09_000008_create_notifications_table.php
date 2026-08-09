<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // task_due, workflow_step, mention, system
            $table->string('titre');
            $table->text('message');
            $table->string('lien')->nullable(); // Lien vers la ressource concernée
            $table->boolean('lu')->default(false);
            $table->dateTime('lu_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('lu');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
