<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Phase 1: add nullable column
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('category_name');
        });

        // Phase 2: backfill in chunks (no model events)
        DB::table('categories')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) {
                // collect current slugs to avoid duplicates within the chunk
                $existing = DB::table('categories')->pluck('slug')->filter()->all();
                $taken = array_flip($existing);

                $updates = [];
                foreach ($rows as $row) {
                    $base = Str::slug($row->category_name ?? '');
                    if ($base === '') {
                        $base = 'category-'.$row->id; // fallback for empty names
                    }

                    // Ensure uniqueness by adding -2, -3, ...
                    $slug = $base;
                    $n = 2;
                    while (isset($taken[$slug]) ||
                           DB::table('categories')->where('slug', $slug)->exists()) {
                        $slug = "{$base}-{$n}";
                        $n++;
                    }
                    $taken[$slug] = true;

                    $updates[$row->id] = $slug;
                }

                // bulk update (one statement per row; still efficient in chunks)
                foreach ($updates as $id => $slug) {
                    DB::table('categories')->where('id', $id)->update(['slug' => $slug]);
                }
            });

        // Phase 3: enforce NOT NULL + UNIQUE
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug');  // creates a unique index
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
