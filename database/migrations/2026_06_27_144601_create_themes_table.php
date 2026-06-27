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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('panel')->default('ns-conseil'); // ns-conseil, admin, super-admin, allopro
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Colors
            $table->string('primary_color')->default('blue');
            $table->string('success_color')->default('emerald');
            $table->string('warning_color')->default('amber');
            $table->string('danger_color')->default('rose');
            $table->string('info_color')->default('sky');
            $table->string('gray_color')->default('slate');
            
            // Dark mode colors
            $table->string('primary_color_dark')->nullable();
            $table->string('success_color_dark')->nullable();
            $table->string('warning_color_dark')->nullable();
            $table->string('danger_color_dark')->nullable();
            $table->string('info_color_dark')->nullable();
            $table->string('gray_color_dark')->nullable();
            
            // Branding
            $table->string('brand_name')->nullable();
            $table->string('brand_logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            
            // Custom CSS
            $table->text('custom_css')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
