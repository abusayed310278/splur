<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->json('image2')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->string('image2')->nullable()->change(); // revert back if needed
        });
    }
};