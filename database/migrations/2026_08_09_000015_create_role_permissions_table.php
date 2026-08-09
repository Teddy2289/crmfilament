<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->string('resource'); // prospect, partenaire, client, etc.
            $table->string('action'); // view, create, update, delete, etc.
            $table->json('fields')->nullable(); // Champs spécifiques autorisés
            $table->boolean('autorise')->default(true);
            $table->timestamps();
            
            $table->index('role_id');
            $table->index('resource');
            $table->unique(['role_id', 'resource', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
