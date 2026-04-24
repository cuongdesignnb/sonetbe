<?php

namespace Database\Seeders;

use App\Models\Ebook;
use Illuminate\Database\Seeder;

class EbookSeeder extends Seeder
{
    public function run(): void
    {
        $ebooks = [
            [
                'title' => 'ONE-PHONE MEDIA - KỸ NĂNG TẠO THU NHẬP CAO THỜI ĐẠI MỚI',
                'slug' => 'one-phone-media-ky-nang-tao-thu-nhap',
                'description' => 'Sử dụng bao lâu nhập cao bổi ích mới. Biến điện thoại + gia chuyện cả nhân thành thương hiệu Mạ, tài sản và...',
                'author_name' => 'Solobiz Academy',
                'price' => 2468000,
                'original_price' => 5000000,
                'type' => 'ebook',
                'status' => 'published',
                'features' => [
                    'Thấu rõ vai trò của media và thương hiệu cá nhân',
                    'Biết các nguồn công thức tài sao truyền thông',
                    'Biết cách tạo ra các mẫu video/hình ảnh',
                ],
            ],
            [
                'title' => 'AFFILIATE SHOPEE - KIẾM TIỀN VỚI SHOPEE',
                'slug' => 'affiliate-shopee-kiem-tien-voi-shopee',
                'description' => 'Chương trình FREE 2 ngày giúp người mới hiểu đầy đủ Shopee Affiliate, linh to linh tất cả cho bạn!',
                'author_name' => 'Solobiz Academy',
                'price' => 0,
                'type' => 'ebook',
                'status' => 'coming_soon',
                'features' => [
                    'Hiểu đúng Shopee Affiliate và cách kinh doanh',
                    'Biết loại đầu tư đầu đến từng level',
                    'Biết cách biến đầu an toàn và hành động',
                ],
            ],
            [
                'title' => '66 NGÀY TIỀN VUI - XÂY TÀI CHÍNH DỤNG TƯƠNG LAI',
                'slug' => '66-ngay-tien-vui-xay-tai-chinh',
                'description' => 'Hành trình 66 ngày giúp bạn hiểu - kiên trì - và xây dựng thành ích và tiền bạc.',
                'author_name' => 'Solobiz Academy',
                'price' => 1000000,
                'original_price' => 1500000,
                'type' => 'book',
                'status' => 'coming_soon',
                'features' => [
                    'Hiểu rõ thực trạng tài chính cá nhân của mình',
                    'Biết cách phân bổ - Kiểm soát - tối ưu chi tiêu',
                    'Thất bật Magic Number - con số tài chính an toàn',
                ],
            ],
            [
                'title' => '[SIÊU TRAFFIC] XÂY NHẬN HIỆU - DỤNG CƠ ĐỎ',
                'slug' => 'sieu-traffic-xay-nhan-hieu-dung-co-do',
                'description' => 'Sự kiện Zoom huấn luyện thật kiệp giúp bạn từ từ đầu đến nghiệp.',
                'author_name' => 'Personal Brand',
                'price' => 0,
                'original_price' => 999000,
                'type' => 'guide',
                'status' => 'coming_soon',
                'features' => [
                    'Hiểu bản chất của Thương Hiệu Cá Nhân',
                    'Biết cách tìm "Song match" của bản thân',
                    'Có bản đồ chi tiết tạo nguồn thu nhập thứ 2',
                ],
            ],
            [
                'title' => '90 NGÀY - XÂY NHẬN HIỆU - DỤNG CƠ ĐỎ',
                'slug' => '90-ngay-xay-nhan-hieu-dung-co-do',
                'description' => 'Chương trình huấn luyện bài bản giúp bạn từ thu nhập hàng ngàn $$$ để xây dựng Thương Hiệu Cá Nhân.',
                'author_name' => 'Personal Brand',
                'price' => 3300000,
                'original_price' => 5000000,
                'type' => 'guide',
                'status' => 'coming_soon',
                'features' => [
                    'Sở hữu Content System chuyên biệt cho Thương Hiệu',
                    'Biết cách affiliate đa kênh không cần ads',
                    'SEO',
                ],
            ],
        ];

        foreach ($ebooks as $data) {
            Ebook::create($data);
        }
    }
}
