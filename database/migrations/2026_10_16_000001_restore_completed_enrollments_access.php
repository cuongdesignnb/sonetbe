<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Finishing a course used to flip the enrollment status to "completed",
     * which every access check treats as "not enrolled" — students were locked
     * out of courses they had already bought. Completion is now tracked only
     * through completed_at, so restore access for everyone affected.
     */
    public function up(): void
    {
        DB::table('enrollments')
            ->where('status', 'completed')
            ->update([
                'status' => 'active',
                'completed_at' => DB::raw('COALESCE(completed_at, updated_at)'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Not reversible: re-locking students out is never wanted.
    }
};
