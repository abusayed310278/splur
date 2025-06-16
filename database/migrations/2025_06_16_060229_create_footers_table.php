<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('footers', function (Blueprint $table) {
            $table->id();
            // Social Icons + Links
            $table->string('facebook_icon')->nullable();
            $table->string('facebook_link')->nullable();
            $table->string('instagram_icon')->nullable();
            $table->string('instagram_link')->nullable();
            $table->string('linkedin_icon')->nullable();
            $table->string('linkedin_link')->nullable();

            $table->string('twitter_icon')->nullable();
            $table->string('twitter_link')->nullable();

            // Store Links
            $table->string('app_store')->nullable();
            $table->string('google_play')->nullable();

            // Other Info
            $table->string('bg_color')->nullable();
            $table->text('copy_rights')->nullable();

            // Single JSON field to store menus as objects [{name, link}, ...]
            $table->json('menus')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footers');
    }
};
