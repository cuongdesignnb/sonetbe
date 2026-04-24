<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::all();

        if ($courses->isEmpty()) {
            $this->command->warn('Không có khóa học nào – bỏ qua seed review.');
            return;
        }

        // Danh sách tên Việt Nam thật
        $names = [
            'Nguyễn Thị Hồng', 'Trần Văn Minh', 'Lê Thị Mai', 'Phạm Đức Anh',
            'Hoàng Thị Lan', 'Vũ Minh Tuấn', 'Đặng Thị Ngọc', 'Bùi Quang Huy',
            'Ngô Thị Thanh', 'Đỗ Văn Long', 'Phan Thị Yến', 'Lý Hoàng Nam',
            'Trịnh Thị Hạnh', 'Dương Minh Khoa', 'Hà Thị Trang', 'Mai Văn Đức',
            'Tô Thị Phương', 'Đinh Quốc Bảo', 'Lương Thị Hà', 'Cao Minh Trí',
            'Nguyễn Hữu Thắng', 'Trần Thị Bích', 'Lê Quốc Việt', 'Phạm Thị Dung',
            'Hoàng Văn Sơn', 'Vũ Thị Linh', 'Đặng Minh Phúc', 'Bùi Thị Oanh',
            'Ngô Văn Tài', 'Đỗ Thị Kim', 'Phan Văn Hải', 'Lý Thị Ngân',
            'Trịnh Văn Hoàng', 'Dương Thị Thảo', 'Hà Minh Quân', 'Mai Thị Huệ',
        ];

        // Các mẫu comment 5 sao
        $comments5 = [
            'Khóa học tuyệt vời! Kiến thức rất thực tế, áp dụng được ngay vào công việc. Cảm ơn anh giảng viên rất nhiều!',
            'Mình đã học rất nhiều khóa online nhưng đây là khóa chất lượng nhất. Nội dung rõ ràng, dễ hiểu, lộ trình bài bản.',
            'Sau khi học xong, mình đã tăng được 50K followers trong 2 tháng. Quá xứng đáng với số tiền bỏ ra!',
            'Giảng viên giảng rất hay, nhiệt tình giải đáp mọi thắc mắc. Recommend cho tất cả mọi người.',
            'Nội dung cập nhật theo xu hướng mới nhất, không bị lỗi thời. Đặc biệt phần thực hành rất bổ ích.',
            'Mình đã áp dụng ngay và thấy hiệu quả rõ rệt. Doanh thu tăng 200% chỉ sau 1 tháng!',
            'Khóa học cover đầy đủ từ cơ bản đến nâng cao. Phù hợp cho cả người mới bắt đầu.',
            'Cực kỳ hài lòng! Video chất lượng cao, slide đẹp, bài tập thực hành phong phú.',
            '5 sao xứng đáng! Mình đã giới thiệu cho cả team công ty cùng học. Ai cũng khen.',
            'Đây là khoản đầu tư tốt nhất mình từng bỏ ra cho việc học. ROI cực cao!',
            'Anh giảng viên chia sẻ rất thật, không giấu nghề. Nhiều tip hay mình chưa thấy ở đâu khác.',
            'Học xong mình tự tin hơn hẳn khi triển khai chiến dịch. Chất lượng vượt kỳ vọng!',
        ];

        // Các mẫu comment 4 sao
        $comments4 = [
            'Khóa học rất tốt, nội dung đầy đủ. Chỉ tiếc là phần nâng cao có thể đi sâu hơn một chút.',
            'Nhìn chung rất hài lòng. Giảng viên nhiệt tình, tài liệu đầy đủ. Mong có thêm case study thực tế.',
            'Kiến thức rất hay và thực tế. Nếu có thêm bài tập thực hành thì sẽ hoàn hảo hơn.',
            '4 sao vì nội dung tốt nhưng một số video hơi dài. Overall vẫn rất đáng học.',
            'Khóa học chất lượng, đáng tiền. Chỉ mong phần Q&A có thể phản hồi nhanh hơn.',
            'Rất tốt cho người mới. Mình đã có kinh nghiệm nên một số phần hơi cơ bản, nhưng vẫn học được nhiều.',
        ];

        // Các mẫu comment 3 sao (ít)
        $comments3 = [
            'Khóa học ổn, nội dung khá đầy đủ nhưng mình mong chờ nhiều hơn ở phần thực hành.',
            'Nội dung tương đối tốt. Phần lý thuyết hơi nhiều so với thực hành.',
        ];

        $now = Carbon::now();

        foreach ($courses as $course) {
            // Mỗi khóa học seed 8-15 reviews
            $reviewCount = rand(8, 15);
            $usedNames = [];

            for ($i = 0; $i < $reviewCount; $i++) {
                // Chọn tên chưa dùng cho khóa học này
                $availableNames = array_diff($names, $usedNames);
                if (empty($availableNames)) {
                    break;
                }
                $name = $availableNames[array_rand($availableNames)];
                $usedNames[] = $name;

                // Phân bổ rating: ~60% 5 sao, ~30% 4 sao, ~10% 3 sao
                $rand = rand(1, 100);
                if ($rand <= 60) {
                    $rating = 5;
                    $comment = $comments5[array_rand($comments5)];
                } elseif ($rand <= 90) {
                    $rating = 4;
                    $comment = $comments4[array_rand($comments4)];
                } else {
                    $rating = 3;
                    $comment = $comments3[array_rand($comments3)];
                }

                // Ngày tạo ngẫu nhiên trong 6 tháng gần
                $daysAgo = rand(1, 180);
                $createdAt = $now->copy()->subDays($daysAgo)->addHours(rand(6, 22))->addMinutes(rand(0, 59));

                Review::create([
                    'user_id'       => null,
                    'course_id'     => $course->id,
                    'reviewer_name' => $name,
                    'rating'        => $rating,
                    'comment'       => $comment,
                    'is_approved'   => true,
                    'approved_at'   => $createdAt->copy()->addHours(rand(1, 48)),
                    'created_at'    => $createdAt,
                    'updated_at'    => $createdAt,
                ]);
            }

            $this->command->info("✓ Đã seed {$reviewCount} đánh giá cho: {$course->title}");
        }
    }
}
