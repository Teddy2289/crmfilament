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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('prospect_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('partenaire_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('opportunite_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('rendez_vous_id')->nullable()->constrained('rendez_vous')->onDelete('set null');
            $table->foreignId('appel_id')->nullable()->constrained()->onDelete('set null');
            $table->string('description');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->nullable(); // En minutes
            $table->string('type')->default('work'); // work, break, meeting
            $table->boolean('billable')->default(true);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('start_time');
            $table->index('end_time');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
