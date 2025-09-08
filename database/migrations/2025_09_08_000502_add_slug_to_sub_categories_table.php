<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Phase 1: add the column as nullable so nothing breaks
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // Phase 2: backfill existing rows in chunks (no model events)
        DB::table('sub_categories')->orderBy('id')->chunkById(1000, function ($rows) {
            foreach ($rows as $row) {
                $base = Str::slug($row->name ?? '');
                if ($base === '') {
                    $base = 'subcategory-'.$row->id;
                }

                // Ensure uniqueness within the SAME category_id
                $slug = $base;
                $n = 2;
                while (
                    DB::table('sub_categories')
                        ->where('category_id', $row->category_id)
                        ->where('slug', $slug)
                        ->where('id', '!=', $row->id)
                        ->exists()
                ) {
                    $slug = "{$base}-{$n}";
                    $n++;
                }

                DB::table('sub_categories')->where('id', $row->id)->update(['slug' => $slug]);
            }
        });

        // Phase 3: make it NOT NULL and add a composite UNIQUE index (category_id, slug)
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique(['category_id', 'slug'], 'sub_categories_category_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropUnique('sub_categories_category_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
