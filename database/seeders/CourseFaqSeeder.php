<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseFaq;
use Illuminate\Database\Seeder;

class CourseFaqSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::query()->get(['id', 'title']);

        foreach ($courses as $course) {
            // Avoid duplicating if already seeded
            $exists = CourseFaq::query()->where('course_id', $course->id)->exists();
            if ($exists) continue;

            CourseFaq::create([
                'course_id' => $course->id,
                'question' => 'Học xong có tự triển khai kênh được không?',
                'answer' => 'Có. Khóa học tập trung thực hành theo từng kênh, có checklist và KPI rõ ràng để bạn áp dụng ngay.',
                'order' => 1,
                'is_active' => true,
            ]);

            CourseFaq::create([
                'course_id' => $course->id,
                'question' => 'Có cần kinh nghiệm chạy ads trước không?',
                'answer' => 'Không bắt buộc. Lộ trình có phần nền tảng, từ setup tài khoản đến tối ưu chỉ số.',
                'order' => 2,
                'is_active' => true,
            ]);

            CourseFaq::create([
                'course_id' => $course->id,
                'question' => 'Lộ trình học bao lâu thì ra kết quả?',
                'answer' => 'Tuỳ kênh, trung bình 4-8 tuần có thể thấy tăng trưởng rõ rệt nếu làm đúng checklist.',
                'order' => 3,
                'is_active' => true,
            ]);

            CourseFaq::create([
                'course_id' => $course->id,
                'question' => 'Có template và file mẫu không?',
                'answer' => 'Có. Bạn sẽ nhận template content plan, checklist tối ưu và file mẫu chiến dịch.',
                'order' => 4,
                'is_active' => true,
            ]);
        }
    }
}
