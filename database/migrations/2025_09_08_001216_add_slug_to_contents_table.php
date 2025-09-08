<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Phase 1: add nullable slug column
        Schema::table('contents', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('heading');
        });

        // Phase 2: backfill slugs from heading
        DB::table('contents')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $base = Str::slug($row->heading ?? '');
                if ($base === '') {
                    $base = 'content-'.$row->id;
                }

                $slug = $base;
                $n = 2;
                while (
                    DB::table('contents')
                        ->where('slug', $slug)
                        ->where('id', '!=', $row->id)
                        ->exists()
                ) {
                    $slug = "{$base}-{$n}";
                    $n++;
                }

                DB::table('contents')->where('id', $row->id)->update(['slug' => $slug]);
            }
        });

        // Phase 3: enforce not null + unique
        Schema::table('contents', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug', 'contents_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropUnique('contents_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
