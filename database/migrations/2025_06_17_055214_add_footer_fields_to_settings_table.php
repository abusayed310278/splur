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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('facebook_icon')->nullable();
            $table->string('facebook_link')->nullable();
            $table->string('twitter_icon')->nullable();
            $table->string('twitter_link')->nullable();
            $table->string('linkedin_icon')->nullable();
            $table->string('linkedin_link')->nullable();
            $table->string('instagram_icon')->nullable();
            $table->string('instagram_link')->nullable();

            $table->string('app_store_icon')->nullable();
            $table->string('app_store_link')->nullable();
            $table->string('google_play_icon')->nullable();
            $table->string('google_play_link')->nullable();

            $table->text('copyright')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_icon',
                'facebook_link',
                'twitter_icon',
                'twitter_link',
                'linkedin_icon',
                'linkedin_link',
                'instagram_icon',
                'instagram_link',
                'app_store_icon',
                'app_store_link',
                'google_play_icon',
                'google_play_link',
                'copyright',
            ]);
        });
    }
};
