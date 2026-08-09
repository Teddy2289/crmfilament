<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nom');
            $table->boolean('par_defaut')->default(false);
            $table->json('widgets_config'); // Configuration des widgets
            $table->json('layout_config'); // Configuration du layout
            $table->timestamps();
            
            $table->index('user_id');
            $table->unique(['user_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dashboards');
    }
};
