<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'course_payments',
            'lesson_progress',
            'enrollments',
            'reviews',
            'course_faqs',
            'lessons',
            'course_sections',
            'courses',
            'categories',
            'blog_posts',
            'blog_categories',
            // keep media_assets/media_folders to preserve uploaded library
            'sepay_transactions',
            'personal_access_tokens',
            'settings',
            'users',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->call([
            AdminUserSeeder::class,
            StudentUserSeeder::class,
            CategorySeeder::class,
            CourseSeeder::class,
            CourseCurriculumSeeder::class,
            CourseFaqSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
