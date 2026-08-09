<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->onDelete('cascade');
            $table->foreignId('from_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->foreignId('to_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->text('commentaire')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('workflow_instance_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_histories');
    }
};
