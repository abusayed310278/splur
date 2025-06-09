<?php 

// database/migrations/xxxx_xx_xx_create_comment_votes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comment_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('comment_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('vote'); // 1 for upvote, -1 for downvote
            $table->timestamps();

            $table->unique(['user_id', 'comment_id']); // prevent double voting
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_votes');
    }
};
