<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE courses MODIFY COLUMN status ENUM('draft', 'published', 'archived', 'coming_soon') DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE courses MODIFY COLUMN status ENUM('draft', 'published', 'archived') DEFAULT 'draft'");
    }
};
