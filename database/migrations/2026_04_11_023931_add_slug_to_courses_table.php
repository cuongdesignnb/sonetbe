<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
        });

        // Generate slugs for existing courses
        $courses = \App\Models\Course::withTrashed()->get();
        foreach ($courses as $course) {
            $base = \Illuminate\Support\Str::slug($course->title);
            if (!$base) $base = 'course-' . $course->id;
            $slug = $base;
            $i = 2;
            while (\App\Models\Course::withTrashed()->where('slug', $slug)->where('id', '!=', $course->id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $course->slug = $slug;
            $course->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
