<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rdv_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rendez_vous_id')->constrained('rendez_vous')->onDelete('cascade');
            $table->string('rdvable_type')->nullable();
            $table->unsignedBigInteger('rdvable_id')->nullable();
            $table->string('method')->nullable(); // email|phone|user_choice|auto
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rdv_associations');
    }
};
