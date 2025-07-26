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
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            // Step 1: Handle existing data in 'image2'
            // Option A: If 'image2' could contain non-JSON strings (e.g., single image paths)
            // Convert existing non-NULL strings into a JSON array, or '{}' for NULLs
            // This is the most robust way if you're unsure of content.
            DB::statement("
                UPDATE contents
                SET image2 = CASE
                    WHEN image2 IS NULL OR image2 = '' THEN 'null'::text -- Or '[]'::text or '{}'::text
                    WHEN image2 ~ '^\\s*(\\[|\\{).*' THEN image2 -- Already looks like JSON array or object
                    ELSE to_json(ARRAY[image2::text])::text -- Wrap single string in a JSON array
                END
                WHERE image2 IS NOT NULL OR image2 != '';
            ");

            // Step 2: Change the column type to JSON
            // Now, cast the column explicitly using the 'USING' clause.
            // PostgreSQL will now have valid JSON strings to cast.
            DB::statement('ALTER TABLE contents ALTER COLUMN image2 TYPE JSONB USING image2::jsonb'); // Using JSONB for efficiency
                                                                                                    // If you want JSON, use image2::json
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            // Revert the column type back to character varying(255)
            // When converting back from JSON/JSONB, you need to decide how to revert the data.
            // For simplicity, this example casts it back to text, which might result in JSON string representations.
            DB::statement('ALTER TABLE contents ALTER COLUMN image2 TYPE VARCHAR(255) USING image2::text');

            // Set the column back to nullable or not, depending on your original schema
            // $table->string('image2', 255)->nullable()->change(); // Adjust nullable/default as per original
        });
    }
};