<?php

namespace Database\Seeders;

use App\Models\Webinar;
use Illuminate\Database\Seeder;

class WebinarSeeder extends Seeder
{
    public function run(): void
    {
        $webinars = [
            [
                'title' => 'NOTION - BỘ NÃO THỨ 2 CHO NGƯỜI XÂY THƯƠNG HIỆU & AFFILIATE',
                'slug' => 'notion-bo-nao-thu-2-affiliate',
                'description' => 'Xem những Zoom webinar hướng dẫn thiết kế để trở thành một chuyên gia quản lý và trình bày.',
                'instructor_name' => 'Mrs. Hiền Úi',
                'scheduled_at' => '2026-03-06 14:00:00',
                'views_count' => 8100,
                'status' => 'upcoming',
                'is_free' => true,
                'duration_minutes' => 90,
            ],
            [
                'title' => 'HÀNH TRÌNH 10 NGÀY TỪ 0 ĐẾN 1 TRIỆU VIEW',
                'slug' => 'hanh-trinh-10-ngay-1-trieu-view',
                'description' => 'Một buổi chia sẻ đặc biệt "cầm tay chỉ việc", từ cách chọn chủ đề, lên ý tưởng, viết bài, đăng bài cho tới cả...',
                'instructor_name' => 'Nhà Đại Bàng K2001',
                'scheduled_at' => '2026-03-06 14:00:00',
                'views_count' => 0,
                'status' => 'upcoming',
                'is_free' => true,
                'duration_minutes' => 120,
            ],
            [
                'title' => 'KHƠI NGUỒN ĐỒNG THU NHẬP THỨ HAI CHỈ VỚI ...0 ĐỒNG',
                'slug' => 'khoi-nguon-dong-thu-nhap-thu-hai',
                'description' => 'Khai thác trải nghiệm đáng quý của bạn và biến nó thành "tài sản".',
                'instructor_name' => 'Nhà Sư Tử 2601',
                'scheduled_at' => '2026-03-07 20:00:00',
                'views_count' => 2600,
                'status' => 'upcoming',
                'is_free' => true,
                'duration_minutes' => 60,
                'benefits' => [
                    '3 Bước xác định Lợi Thế Cạnh Tranh từ chính câu chuyện cuộc đời bạn',
                    'Cầm tay chỉ việc cách định vị Thương Hiệu cho bản thân',
                    'Tặng AI bot giúp đánh giá Facebook có đúng định vị + gợi ý điều chỉnh content',
                    'Tăng Trưởng Cùng Shopee - Bí quyết tìm sản phẩm hot trên sàn chưa ai chỉ',
                    'Nắm Chìa Khoá Tài Chính 2026 để thong dong những chặng đường sau',
                    'Game CUỘC ĐỜI do bạn làm CHỦ chính thức bắt đầu',
                ],
                'speakers' => [
                    ['name' => 'Ms Vũ Yến', 'role' => 'Chuyên gia Marketing', 'avatar' => null],
                    ['name' => 'Dr Chúc', 'role' => 'Chuyên gia Tài chính', 'avatar' => null],
                    ['name' => 'Ms Anna Huyền', 'role' => 'Chuyên gia Shopee', 'avatar' => null],
                ],
            ],
            [
                'title' => 'XÂY DỰNG KÊNH YOUTUBE TỪ CON SỐ 0',
                'slug' => 'xay-dung-kenh-youtube-tu-0',
                'description' => 'Hướng dẫn từ A-Z cách xây dựng kênh YouTube kiếm tiền.',
                'instructor_name' => 'Anh Chiến',
                'scheduled_at' => '2026-02-20 14:00:00',
                'views_count' => 5200,
                'status' => 'completed',
                'is_free' => true,
                'duration_minutes' => 90,
            ],
        ];

        foreach ($webinars as $data) {
            Webinar::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
