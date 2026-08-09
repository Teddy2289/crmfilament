<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nom');
            $table->string('token')->unique();
            $table->json('permissions')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
