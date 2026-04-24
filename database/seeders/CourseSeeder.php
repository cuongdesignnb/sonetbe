<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        if (!$admin) {
            return;
        }

        $categories = Category::query()->whereNull('parent_id')->get();
        if ($categories->isEmpty()) {
            return;
        }

        $seedCourses = [
            ['title' => 'TikTok Growth: Từ 0 lên 100K followers', 'level' => 'beginner'],
            ['title' => 'TikTok Ads thực chiến: Tối ưu CPA', 'level' => 'advanced'],
            ['title' => 'Facebook Ads 2026: Chiến dịch chuyển đổi', 'level' => 'intermediate'],
            ['title' => 'Instagram Reels & Content Plan', 'level' => 'beginner'],
            ['title' => 'YouTube SEO + Shorts: Tăng view bền vững', 'level' => 'intermediate'],
            ['title' => 'LinkedIn B2B Lead Gen & Personal Branding', 'level' => 'advanced'],
        ];

        foreach ($seedCourses as $row) {
            $category = $categories->random();

            $sampleMarketing = [
                'promo' => [
                    'enabled' => true,
                    'text' => '🎁 ƯU ĐÃI ĐẶC BIỆT: Giảm 20% khóa học social media hôm nay!',
                ],
                'landing_nav' => [
                    'enabled' => true,
                    'logo_url' => '',
                    'items' => [
                        ['id' => 'about', 'label' => 'Về chương trình'],
                        ['id' => 'curriculum', 'label' => 'Lộ trình'],
                        ['id' => 'instructor', 'label' => 'Giảng viên'],
                        ['id' => 'pricing', 'label' => 'Đăng ký'],
                    ],
                ],
                'hero' => [
                    'headline' => $row['title'],
                    'subheadline' => 'Lộ trình rõ ràng, tối ưu nội dung & quảng cáo theo từng kênh',
                    'bullets' => [
                        'Framework xây dựng nội dung theo funnel',
                        'Checklist tối ưu creative, targeting, budget',
                        'Template kế hoạch đăng bài 30 ngày',
                    ],
                    'background_image' => '',
                    'cards' => [],
                ],
                'ticker_texts' => [
                    '🔥 500+ học viên đã đăng ký tuần này',
                    '⭐ Đánh giá 4.9/5 từ học viên',
                    '🎯 Cam kết hoàn tiền nếu không hiệu quả',
                ],
                'pain_point' => [
                    'enabled' => true,
                    'title' => 'Khi cả thế giới đang "Tiêu thụ" video...',
                    'highlight' => 'Tiêu thụ',
                    'description' => 'Mỗi ngày có hàng triệu nội dung được đăng tải. Bạn muốn là người xem hay người kiếm tiền từ nội dung?',
                    'callout_title' => 'Sự thật là',
                    'callout_text' => '95% người làm nội dung thất bại vì không có lộ trình rõ ràng.',
                    'video_url' => '',
                    'cta_text' => 'Bắt đầu ngay',
                    'badges' => [
                        ['text' => '10K+ Views', 'position' => 'top-left'],
                        ['text' => 'Viral Content', 'position' => 'top-right'],
                    ],
                    'community_note' => 'Cùng hơn 5,000 học viên trong cộng đồng',
                ],
                'benefits' => [
                    'enabled' => true,
                    'badge' => 'Cơ hội 2026',
                    'title' => '2026 Không thiếu cơ hội',
                    'highlight' => 'Không thiếu cơ hội',
                    'subtitle' => 'Nắm bắt xu hướng, xây dựng hệ thống tăng trưởng bền vững.',
                    'items' => [
                        [
                            'title' => 'Xây dựng thương hiệu cá nhân',
                            'description' => 'Trở thành chuyên gia được nhận diện trong ngành.',
                            'color' => 'purple',
                        ],
                        [
                            'title' => 'Thu nhập thụ động từ nội dung',
                            'description' => 'Tạo hệ thống content tự sinh tiền 24/7.',
                            'color' => 'green',
                        ],
                        [
                            'title' => 'Mở rộng mạng lưới kinh doanh',
                            'description' => 'Kết nối cộng đồng khách hàng tiềm năng.',
                            'color' => 'blue',
                        ],
                        [
                            'title' => 'Tối ưu quảng cáo hiệu quả',
                            'description' => 'Giảm chi phí CPA, tăng ROI vượt trội.',
                            'color' => 'orange',
                        ],
                    ],
                    'cta_text' => 'Xem lộ trình chi tiết',
                ],
                'target_audience' => [
                    'enabled' => true,
                    'title' => 'Khóa học phù hợp dành cho ai?',
                    'highlight' => 'dành cho ai',
                    'subtitle' => 'Dù bạn mới bắt đầu hay đã có kinh nghiệm, khóa học đều phù hợp.',
                    'personas' => [
                        [
                            'title' => 'Người mới bắt đầu',
                            'description' => 'Chưa có kinh nghiệm, muốn học từ A-Z.',
                            'color' => 'orange',
                        ],
                        [
                            'title' => 'Freelancer / Creator',
                            'description' => 'Muốn tăng thu nhập từ nội dung.',
                            'color' => 'blue',
                        ],
                        [
                            'title' => 'Chủ doanh nghiệp nhỏ',
                            'description' => 'Muốn tự chạy marketing hiệu quả.',
                            'color' => 'green',
                        ],
                    ],
                    'closing_quote' => 'Nếu bạn muốn thay đổi, đây là cơ hội tốt nhất.',
                    'cta_text' => 'Đăng ký ngay',
                ],
                'before_after' => [
                    'enabled' => true,
                    'title' => 'BẠN SẼ TRỞ THÀNH PHIÊN BẢN NÀO?',
                    'subtitle' => 'So sánh trước và sau khi tham gia khóa học',
                    'before' => [
                        'title' => 'Trước khóa học',
                        'items' => [
                            'Không biết bắt đầu từ đâu',
                            'Đăng nội dung không ai xem',
                            'Chạy quảng cáo đốt tiền',
                            'Không có hệ thống rõ ràng',
                        ],
                    ],
                    'after' => [
                        'title' => 'Sau khóa học',
                        'items' => [
                            'Có lộ trình 90 ngày rõ ràng',
                            'Nội dung viral, tăng follower nhanh',
                            'Tối ưu quảng cáo, giảm CPA 50%',
                            'Hệ thống tự động sinh lead',
                        ],
                        'recommended_badge' => 'Khuyên dùng',
                    ],
                    'bottom_note' => 'Hơn 12,000 học viên đã chuyển đổi thành công!',
                ],
                'workflow' => [
                    'enabled' => true,
                    'badge' => 'Social Growth Workflow',
                    'title' => 'Lộ trình 3 pha tăng trưởng kênh',
                    'subtitle' => 'Mỗi pha có checklist, KPI và lịch đăng bài rõ ràng để team bám theo.',
                    'album' => [],
                    'steps' => [
                        [
                            'title' => 'Định hình chiến lược',
                            'desc' => 'Xác định audience, insight và thông điệp cốt lõi.',
                            'tag' => 'Roadmap 30-90 ngày rõ ràng.',
                        ],
                        [
                            'title' => 'Triển khai & scale',
                            'desc' => 'Sản xuất nội dung, test creative và scale chiến dịch tốt nhất.',
                            'tag' => 'Tối ưu CPA mỗi tuần.',
                        ],
                        [
                            'title' => 'Nhân bản & bảo trì',
                            'desc' => 'Chuẩn hóa quy trình, lập lịch nội dung và báo cáo.',
                            'tag' => 'Tăng trưởng bền vững.',
                        ],
                    ],
                ],
                'instructor_extra' => [
                    'enabled' => true,
                    'badge' => 'TRAINER #1',
                    'label' => 'GIẢNG VIÊN',
                    'title' => 'Chuyên gia Social Media Marketing',
                    'bio_extended' => 'Hơn 8 năm kinh nghiệm trong lĩnh vực digital marketing, đã đào tạo hơn 12,000 học viên thành công.',
                    'expertise' => [
                        'TikTok & Reels Growth Strategy',
                        'Facebook & Instagram Ads Optimization',
                        'Content Marketing & Viral Framework',
                        'E-commerce Social Selling',
                    ],
                    'closing_quote' => 'Thành công không đến từ may mắn, mà từ hệ thống.',
                    'achievements' => [
                        ['value' => '12K+', 'label' => 'Học viên'],
                        ['value' => '8 năm', 'label' => 'Kinh nghiệm'],
                        ['value' => '50M+', 'label' => 'Lượt xem'],
                    ],
                    'image' => '',
                    'cta_text' => 'Tìm hiểu thêm',
                ],
                'testimonials' => [
                    'enabled' => true,
                    'title' => 'CẢM NHẬN HỌC VIÊN',
                    'videos' => [],
                    'feedback_title' => 'PHẢN HỒI TỪ CỘNG ĐỒNG',
                    'feedback_images' => [],
                    'gallery_title' => 'HÌNH ẢNH CÁC BUỔI ĐÀO TẠO',
                    'gallery_images' => [],
                ],
                'final_cta' => [
                    'enabled' => true,
                    'title' => 'SẴN SÀNG THAY ĐỔI?',
                    'subtitle' => 'Đăng ký ngay hôm nay để nhận ưu đãi đặc biệt.',
                    'cta_text' => 'ĐĂNG KÝ NGAY',
                    'social_proof' => '500+ người đã đăng ký trong tuần này',
                ],
                'urgency' => [
                    'enabled' => true,
                    'total_spots' => 100,
                    'remaining_spots' => 23,
                    'countdown_to' => '2026-04-01T00:00:00',
                ],
                'stats' => [
                    ['value' => '12000+', 'label' => 'Học viên'],
                    ['value' => '4.8/5', 'label' => 'Đánh giá'],
                    ['value' => '2.7x', 'label' => 'Tăng trưởng'],
                ],
                'floating_bar' => [
                    'enabled' => true,
                    'viewer_count' => 47,
                ],
            ];

            Course::updateOrCreate(
                [
                    'title' => $row['title'],
                    'instructor_id' => $admin->id,
                ],
                [
                    'description' => 'Khóa học thực hành với dự án mẫu. Nội dung cập nhật, bài tập thực tế và hướng dẫn chi tiết.',
                    'price' => random_int(0, 1) ? random_int(199000, 1499000) : 0,
                    'thumbnail' => null,
                    'preview_video' => null,
                    'category_id' => $category->id,
                    'level' => $row['level'],
                    'duration' => random_int(120, 1200),
                    'status' => 'published',
                    'meta_description' => Str::limit('Khóa học ' . $row['title'] . ' giúp bạn nắm vững kiến thức và làm được dự án thực tế.', 160, ''),
                    'tags' => ['featured', Str::slug($category->name)],
                    'marketing' => $sampleMarketing,
                ]
            );
        }

        $levels = ['beginner', 'intermediate', 'advanced'];

        // Add a few more random courses
        for ($i = 0; $i < 12; $i++) {
            $category = $categories->random();
            $title = $category->name . ' thực chiến #' . ($i + 1);

            $marketing = [
                'promo' => [
                    'enabled' => (bool) random_int(0, 1),
                    'text' => '🎁 ƯU ĐÃI HÔM NAY: Giảm 15% cho 50 bạn đầu tiên!',
                ],
                'landing_nav' => [
                    'enabled' => true,
                    'logo_url' => '',
                    'items' => [
                        ['id' => 'about', 'label' => 'Về chương trình'],
                        ['id' => 'curriculum', 'label' => 'Lộ trình'],
                        ['id' => 'instructor', 'label' => 'Giảng viên'],
                        ['id' => 'pricing', 'label' => 'Đăng ký'],
                    ],
                ],
                'hero' => [
                    'headline' => $title,
                    'subheadline' => 'Học theo lộ trình - tối ưu nội dung - có checklist',
                    'bullets' => [
                        'Lộ trình rõ ràng, có roadmap',
                        'Bài tập tối ưu content/ads',
                        'Template + checklist đính kèm',
                    ],
                    'background_image' => '',
                    'cards' => [],
                ],
                'ticker_texts' => [
                    '🔥 Đang có ưu đãi đặc biệt',
                    '⭐ 4.9/5 đánh giá từ học viên',
                ],
                'pain_point' => [
                    'enabled' => (bool) random_int(0, 1),
                    'title' => 'Bạn đang gặp khó khăn với social media?',
                    'highlight' => 'khó khăn',
                    'description' => 'Đừng lo, khóa học này sẽ giúp bạn.',
                    'callout_title' => 'Sự thật',
                    'callout_text' => '90% người làm social media không có chiến lược rõ ràng.',
                    'cta_text' => 'Xem giải pháp',
                ],
                'benefits' => [
                    'enabled' => (bool) random_int(0, 1),
                    'badge' => 'Lợi ích',
                    'title' => 'Bạn sẽ nhận được gì?',
                    'highlight' => 'nhận được gì',
                    'subtitle' => 'Kiến thức và kỹ năng thực chiến.',
                    'items' => [
                        ['title' => 'Chiến lược rõ ràng', 'description' => 'Có roadmap từng bước.', 'color' => 'purple'],
                        ['title' => 'Kỹ năng thực hành', 'description' => 'Bài tập thực tế.', 'color' => 'green'],
                        ['title' => 'Cộng đồng hỗ trợ', 'description' => 'Nhóm học viên sôi nổi.', 'color' => 'blue'],
                    ],
                    'cta_text' => 'Xem chi tiết',
                ],
                'target_audience' => [
                    'enabled' => (bool) random_int(0, 1),
                    'title' => 'Khóa học dành cho ai?',
                    'highlight' => 'dành cho ai',
                    'subtitle' => 'Phù hợp mọi trình độ.',
                    'personas' => [
                        ['title' => 'Người mới', 'description' => 'Bắt đầu từ zero.', 'color' => 'orange'],
                        ['title' => 'Freelancer', 'description' => 'Muốn tăng thu nhập.', 'color' => 'blue'],
                    ],
                    'closing_quote' => 'Cơ hội dành cho tất cả mọi người.',
                    'cta_text' => 'Đăng ký ngay',
                ],
                'before_after' => [
                    'enabled' => (bool) random_int(0, 1),
                    'title' => 'TRƯỚC VÀ SAU KHÓA HỌC',
                    'subtitle' => '',
                    'before' => [
                        'title' => 'Trước',
                        'items' => ['Không biết bắt đầu', 'Content không hiệu quả', 'Tốn tiền quảng cáo'],
                    ],
                    'after' => [
                        'title' => 'Sau',
                        'items' => ['Có lộ trình rõ ràng', 'Content thu hút', 'Tối ưu chi phí'],
                        'recommended_badge' => 'Khuyên dùng',
                    ],
                    'bottom_note' => 'Hàng nghìn học viên đã thành công!',
                ],
                'workflow' => [
                    'enabled' => (bool) random_int(0, 1),
                    'badge' => 'Social Growth',
                    'title' => 'Lộ trình 3 pha tăng trưởng kênh',
                    'subtitle' => 'Chia nhỏ mục tiêu theo từng pha để dễ triển khai và theo dõi.',
                    'album' => [],
                    'steps' => [
                        ['title' => 'Định hình chiến lược', 'desc' => 'Xác định audience, content pillars, KPI.', 'tag' => 'Roadmap rõ ràng.'],
                        ['title' => 'Triển khai & scale', 'desc' => 'Test creative, scale nội dung hiệu quả.', 'tag' => 'Tối ưu chi phí.'],
                        ['title' => 'Nhân bản & bảo trì', 'desc' => 'Chuẩn hóa vận hành và nhân bản mô hình.', 'tag' => 'Bền vững.'],
                    ],
                ],
                'instructor_extra' => [
                    'enabled' => (bool) random_int(0, 1),
                    'badge' => 'TRAINER',
                    'label' => 'GIẢNG VIÊN',
                    'title' => 'Chuyên gia Marketing',
                    'bio_extended' => 'Nhiều năm kinh nghiệm trong lĩnh vực digital marketing.',
                    'expertise' => ['Growth Strategy', 'Content Marketing', 'Paid Ads'],
                    'achievements' => [
                        ['value' => (string) random_int(1000, 10000) . '+', 'label' => 'Học viên'],
                        ['value' => random_int(3, 10) . ' năm', 'label' => 'Kinh nghiệm'],
                    ],
                    'image' => '',
                    'cta_text' => 'Tìm hiểu thêm',
                ],
                'testimonials' => [
                    'enabled' => (bool) random_int(0, 1),
                    'title' => 'CẢM NHẬN HỌC VIÊN',
                    'videos' => [],
                    'feedback_title' => 'PHẢN HỒI TỪ CỘNG ĐỒNG',
                    'feedback_images' => [],
                    'gallery_title' => 'HÌNH ẢNH ĐÀO TẠO',
                    'gallery_images' => [],
                ],
                'final_cta' => [
                    'enabled' => (bool) random_int(0, 1),
                    'title' => 'SẴN SÀNG BẮT ĐẦU?',
                    'subtitle' => 'Đăng ký ngay để không bỏ lỡ.',
                    'cta_text' => 'ĐĂNG KÝ NGAY',
                    'social_proof' => random_int(50, 500) . '+ người đã đăng ký',
                ],
                'urgency' => [
                    'enabled' => (bool) random_int(0, 1),
                    'total_spots' => 100,
                    'remaining_spots' => random_int(5, 40),
                    'countdown_to' => '2026-04-01T00:00:00',
                ],
                'stats' => [
                    ['value' => (string) random_int(800, 12000), 'label' => 'Học viên'],
                    ['value' => number_format(random_int(45, 50) / 10, 1) . '/5', 'label' => 'Đánh giá'],
                    ['value' => (string) random_int(2, 5) . 'x', 'label' => 'Tăng trưởng'],
                ],
                'floating_bar' => [
                    'enabled' => (bool) random_int(0, 1),
                    'viewer_count' => random_int(15, 80),
                ],
            ];

            Course::updateOrCreate(
                [
                    'title' => $title,
                    'instructor_id' => $admin->id,
                ],
                [
                    'description' => 'Nội dung theo lộ trình, tối ưu content & ads cho kênh social media.',
                    'price' => random_int(0, 1) ? random_int(99000, 999000) : 0,
                    'thumbnail' => null,
                    'preview_video' => null,
                    'category_id' => $category->id,
                    'level' => $levels[array_rand($levels)],
                    'duration' => random_int(60, 900),
                    'status' => 'published',
                    'meta_description' => Str::limit('Khóa học ' . $title, 160, ''),
                    'tags' => [Str::slug($category->name)],
                    'marketing' => $marketing,
                ]
            );
        }
    }
}
