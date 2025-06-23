<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('advertisings', function (Blueprint $table) {
            $table->dropColumn('key');
        });
    }

    public function down(): void
    {
        Schema::table('advertisings', function (Blueprint $table) {
            $table->string('key')->nullable(); // Or use original definition
        });
    }
};
