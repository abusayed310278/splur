<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Don't forget this import

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define all valid statuses, including the new ones
        $allValidStatuses = [
            'Draft',
            'Review',
            'Approved',
            'Published',
            'Archived',
            'Needs Revision',
            'Rejected',
            // Keep 'active' and 'pending' temporarily if you have a lot of data
            // and want to do the update in a separate step or just allow them
            // during the transition. For a clean solution, we'll map them.
        ];

        Schema::table('contents', function (Blueprint $table) use ($allValidStatuses) {
            // First, remove any existing CHECK constraints on the status column
            // This is important if you had a previous constraint for 'pending'/'active'
            // You might need to find the exact constraint name if it exists.
            // For simplicity, we'll assume no named constraint or it's implicitly handled.
            // If you know the name, use: DB::statement('ALTER TABLE contents DROP CONSTRAINT your_old_constraint_name;');

            // Temporarily make the column nullable and remove default to facilitate updates
            $table->string('status', 255)->nullable()->default(null)->change();
        });

        // Step 1: Map existing 'pending' to 'Draft'
        DB::statement("UPDATE contents SET status = 'Draft' WHERE status = 'pending'");

        // Step 2: Map existing 'active' to 'Published'
        DB::statement("UPDATE contents SET status = 'Published' WHERE status = 'active'");

        // Step 3: Add the new CHECK constraint with all desired statuses
        // Note: 'Needs Revision' is used as it's more descriptive than just 'Revision'
        // based on common content workflows and the previous image.
        $newAllowedStatuses = [
            'Draft',
            'Review',
            'Approved',
            'Published',
            'Archived',
            'Needs Revision',
            'Rejected',
        ];

        Schema::table('contents', function (Blueprint $table) use ($newAllowedStatuses) {
            // Re-add the column with the desired default and then the constraint
            $table->string('status', 255)->default('Draft')->change(); // Set 'Draft' as the new default for new rows

            // Add the CHECK constraint to ensure only these values are allowed
            DB::statement("ALTER TABLE contents ADD CONSTRAINT chk_contents_new_status CHECK (status IN ('" . implode("','", $newAllowedStatuses) . "'))");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            // Remove the new CHECK constraint
            DB::statement('ALTER TABLE contents DROP CONSTRAINT chk_contents_new_status');

            // Revert the status column to its original state (e.g., 'character varying(255)' with 'pending' default)
            // This will not revert the data, only the schema.
            // If you need to revert data, you'd need more complex logic here.
            $table->string('status', 255)->default('pending')->change();
        });
    }
};