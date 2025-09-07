<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->unsignedBigInteger('shares_count')->default(0);
        });

        DB::statement("ALTER TABLE contents
            ADD CONSTRAINT chk_contents_nonneg_counts
            CHECK (likes_count >= 0 AND shares_count >= 0)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE contents DROP CONSTRAINT IF EXISTS chk_contents_nonneg_counts");

        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn(['likes_count', 'shares_count']);
        });
    }
};
