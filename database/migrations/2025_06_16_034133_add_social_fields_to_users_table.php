<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('description')->nullable();

            $table->string('instagram_icon')->default('fab fa-instagram');
            $table->string('instagram_link')->nullable();

            $table->string('facebook_icon')->default('fab fa-facebook-f');
            $table->string('facebook_link')->nullable();

            $table->string('youtube_icon')->default('fab fa-youtube');
            $table->string('youtube_link')->nullable();

            $table->string('twitter_icon')->default('fab fa-x-twitter');
            $table->string('twitter_link')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'instagram_icon', 'instagram_link',
                'facebook_icon', 'facebook_link',
                'youtube_icon', 'youtube_link',
                'twitter_icon', 'twitter_link',
            ]);
        });
    }
};
