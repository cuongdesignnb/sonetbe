<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::query()->get();
        if ($courses->isEmpty()) {
            return;
        }

        foreach ($courses as $course) {
            // Keep seeder idempotent: if course already has sections, don't duplicate.
            if ($course->sections()->exists()) {
                continue;
            }

            $sectionCount = random_int(4, 6);
            $lessonGlobalOrder = 1;

            for ($sectionOrder = 1; $sectionOrder <= $sectionCount; $sectionOrder++) {
                $sectionTitle = match ($sectionOrder) {
                    1 => 'Nền tảng kênh & mục tiêu',
                    2 => 'Content Pillars & Format',
                    3 => 'Quảng cáo & Growth',
                    4 => 'Phân tích & Tối ưu',
                    5 => 'Scale & Vận hành',
                    default => 'Tổng kết',
                };

                $section = CourseSection::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'order' => $sectionOrder,
                    ],
                    [
                        'title' => $sectionTitle,
                        'description' => 'Module ' . $sectionOrder . ': ' . Str::limit($course->title, 60, ''),
                    ]
                );

                $lessonCount = random_int(3, 7);

                for ($lessonOrder = 1; $lessonOrder <= $lessonCount; $lessonOrder++) {
                    $isPreview = ($sectionOrder === 1 && $lessonOrder === 1);
                    $minutes = random_int(4, 18);
                    $seconds = $minutes * 60;

                    Lesson::updateOrCreate(
                        [
                            'course_id' => $course->id,
                            'section_id' => $section->id,
                            'order' => $lessonOrder,
                        ],
                        [
                            'title' => sprintf('Bài %d.%d: %s', $sectionOrder, $lessonOrder, $this->lessonTopic($sectionOrder, $lessonOrder)),
                            'description' => 'Bài học thực hành, có ví dụ và checklist hoàn thành.',
                            'duration' => $seconds,
                            'is_preview' => $isPreview,
                            'content' => $this->lessonContent($course->title, $sectionTitle),
                            'resources' => [
                                ['type' => 'link', 'title' => 'Tài liệu tham khảo', 'url' => 'https://developer.mozilla.org/'],
                            ],
                        ]
                    );

                    $lessonGlobalOrder++;
                }
            }
        }
    }

    private function lessonTopic(int $sectionOrder, int $lessonOrder): string
    {
        $topics = [
            1 => ['Tổng quan kênh', 'Xác định audience', 'Đặt KPI', 'Brand voice', 'Checklist khởi động'],
            2 => ['Content pillars', 'Hook 3 giây đầu', 'Script ngắn', 'Storyboard', 'Lịch đăng 30 ngày'],
            3 => ['Thiết lập quảng cáo', 'Targeting', 'Creative testing', 'Retargeting', 'Tối ưu CPA'],
            4 => ['Đọc số liệu', 'A/B testing', 'Tối ưu nội dung', 'Tối ưu ads', 'Quy chuẩn báo cáo'],
            5 => ['Scale chiến dịch', 'Quy trình team', 'Automation', 'Quản trị rủi ro', 'Bảo trì tăng trưởng'],
            6 => ['Tổng kết', 'Checklist tổng', 'Kế hoạch 90 ngày'],
        ];

        $bucket = $topics[$sectionOrder] ?? $topics[1];
        return $bucket[($lessonOrder - 1) % count($bucket)];
    }

    private function lessonContent(string $courseTitle, string $sectionTitle): string
    {
        return "# {$courseTitle}\n\n## {$sectionTitle}\n\n- Mục tiêu: hoàn thành bài học trong 10-20 phút\n- Checklist: xem video, thực hành, tối ưu theo KPI\n\n**Gợi ý:** ghi chú insight, hook và CTA hiệu quả.";
    }
}
