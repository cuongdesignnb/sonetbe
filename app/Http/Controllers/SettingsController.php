<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;

class SettingsController extends Controller
{
    /**
     * Get general site settings
     */
    public function index()
    {
        $defaults = [
            'site.name' => 'Sonnet',
            'site.description' => 'Nền tảng học online hàng đầu Việt Nam',
            'site.url' => 'https://sonnet.vn',
            'site.logo_url' => '/logo.svg',
            'site.favicon_url' => '/favicon.svg',
            'site.contact.email' => 'support@sonnet.vn',
            'site.contact.phone' => '+84 123 456 789',
            'site.contact.address' => '123 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh',
            'site.social.facebook' => 'https://facebook.com/sonnetvn',
            'site.social.youtube' => 'https://youtube.com/@sonnetvn',
            'site.social.instagram' => 'https://instagram.com/sonnetvn',
            'site.social.tiktok' => 'https://tiktok.com/@sonnetvn',
            'site.social.linkedin' => 'https://linkedin.com/company/sonnetvn',
            'site.social.twitter' => 'https://twitter.com/sonnetvn',
            'seo.title_template' => '%s | Sonnet',
            'seo.default_title' => 'Sonnet - Học Online Chất Lượng Cao',
            'seo.default_description' => 'Khám phá hàng ngàn khóa học chất lượng cao từ các chuyên gia hàng đầu. Video HD, học mọi lúc mọi nơi, chứng chỉ được công nhận.',
            'seo.keywords' => 'học online, khóa học, e-learning, sonnet, học trực tuyến',
            'home.hero.badge' => 'Nền tảng học online #1 Việt Nam',
            'home.hero.title_prefix' => 'Mở khóa',
            'home.hero.title_highlight' => 'tiềm năng',
            'home.hero.title_suffix' => 'của bạn',
            'home.hero.subtitle' => 'Khám phá hàng ngàn khóa học chất lượng cao từ các chuyên gia hàng đầu. Video HD, học mọi lúc mọi nơi, chứng chỉ được công nhận.',
            'home.hero.primary_cta' => 'Bắt đầu học miễn phí',
            'home.hero.secondary_cta' => 'Xem giới thiệu',
            'home.stats.courses' => 1000,
            'home.stats.students' => 50000,
            'home.stats.certificates' => 25000,
            'home.stats.countries' => 40,
            'home.stats.rating' => 4.9,
            'home.stats.label_students' => 'Học viên',
            'home.stats.label_courses' => 'Khóa học',
            'home.stats.label_certificates' => 'Ebooks',
            'home.stats.label_countries' => 'Sách xuất bản',
            'home.cta.title_prefix' => 'Sẵn sàng nâng cấp',
            'home.cta.title_highlight' => 'kỹ năng',
            'home.cta.title_suffix' => 'của bạn?',
            'home.cta.subtitle' => 'Tham gia cùng hơn 50,000+ học viên đã thay đổi sự nghiệp của họ thông qua các khóa học chất lượng cao của chúng tôi.',
            'home.cta.primary_cta' => 'Đăng ký miễn phí',
            'home.cta.secondary_cta' => 'Xem khóa học',
            'home.featured.title' => 'Khóa học, Sách & Ebooks nổi bật',
            'home.featured.subtitle' => 'Được thiết kế bởi các chuyên gia hàng đầu, phù hợp cho mọi trình độ',
            'home.featured.button_text' => 'TÌM HIỂU NGAY',
            'home.webinar.badge' => 'Zoom Webinar',
            'home.webinar.title' => 'Zoom Webinar miễn phí & trả phí',
            'home.webinar.subtitle' => 'Tham gia học trực tiếp với chuyên gia',
            'home.webinar.tab_upcoming' => 'Sắp tới',
            'home.webinar.tab_completed' => 'Đã hoàn thành',
            'home.webinar.button_detail' => 'Xem chi tiết',
            'home.webinar.button_view_all' => 'Xem tất cả webinar',
            'home.affiliate.title' => 'Chương trình Affiliate - Xây dựng nguồn thu nhập thứ 2!',
            'home.affiliate.description' => 'Nhận hoa hồng lên đến 85% khi giới thiệu khách hàng mua khóa học',
            'home.affiliate.button_text' => 'Tìm hiểu thêm',
            // Footer settings
            'footer.description' => 'Cung cấp các khóa học chất lượng cao với công nghệ streaming video tiên tiến.',
            'footer.copyright_text' => '© ' . date('Y') . ' Sonnet. Tất cả quyền được bảo lưu.',
            'footer.tagline' => 'Made with ❤ in Vietnam',
            'footer.show_social_links' => true,
            'footer.links' => json_encode([
                'courses' => [
                    ['name' => 'Lập trình Web', 'href' => '/categories/1'],
                    ['name' => 'Mobile App', 'href' => '/categories/2'],
                    ['name' => 'UI/UX Design', 'href' => '/categories/3'],
                    ['name' => 'Data Science', 'href' => '/categories/4'],
                ],
                'support' => [
                    ['name' => 'Trung tâm hỗ trợ', 'href' => '/support'],
                    ['name' => 'FAQ', 'href' => '/faq'],
                    ['name' => 'Liên hệ', 'href' => '/contact'],
                    ['name' => 'Góp ý', 'href' => '/feedback'],
                ],
                'legal' => [
                    ['name' => 'Điều khoản sử dụng', 'href' => '/terms'],
                    ['name' => 'Chính sách bảo mật', 'href' => '/privacy'],
                    ['name' => 'Chính sách hoàn tiền', 'href' => '/refund'],
                ],
            ]),
            // Custom code settings
            'custom_code.head_scripts' => '',
            'custom_code.body_start_scripts' => '',
            'custom_code.body_end_scripts' => '',
            'custom_code.custom_css' => '',
        ];

        $resolved = [];
        foreach ($defaults as $key => $default) {
            $resolved[$key] = SettingsService::get($key, $default);
        }

        return response()->json([
            'settings' => [
                'site' => [
                    'name' => $resolved['site.name'],
                    'description' => $resolved['site.description'],
                    'url' => $resolved['site.url'],
                    'logo_url' => $resolved['site.logo_url'],
                    'favicon_url' => $resolved['site.favicon_url'],
                    'contact' => [
                        'email' => $resolved['site.contact.email'],
                        'phone' => $resolved['site.contact.phone'],
                        'address' => $resolved['site.contact.address'],
                    ],
                    'social' => [
                        'facebook' => $resolved['site.social.facebook'],
                        'youtube' => $resolved['site.social.youtube'],
                        'instagram' => $resolved['site.social.instagram'],
                        'tiktok' => $resolved['site.social.tiktok'],
                        'linkedin' => $resolved['site.social.linkedin'],
                        'twitter' => $resolved['site.social.twitter'],
                    ],
                ],
                'seo' => [
                    'title_template' => $resolved['seo.title_template'],
                    'default_title' => $resolved['seo.default_title'],
                    'default_description' => $resolved['seo.default_description'],
                    'keywords' => $resolved['seo.keywords'],
                ],
                'home' => [
                    'hero' => [
                        'badge' => $resolved['home.hero.badge'],
                        'title_prefix' => $resolved['home.hero.title_prefix'],
                        'title_highlight' => $resolved['home.hero.title_highlight'],
                        'title_suffix' => $resolved['home.hero.title_suffix'],
                        'subtitle' => $resolved['home.hero.subtitle'],
                        'primary_cta' => $resolved['home.hero.primary_cta'],
                        'secondary_cta' => $resolved['home.hero.secondary_cta'],
                    ],
                    'stats' => [
                        'courses' => (int) $resolved['home.stats.courses'],
                        'students' => (int) $resolved['home.stats.students'],
                        'certificates' => (int) $resolved['home.stats.certificates'],
                        'countries' => (int) $resolved['home.stats.countries'],
                        'rating' => (float) $resolved['home.stats.rating'],
                        'label_students' => $resolved['home.stats.label_students'],
                        'label_courses' => $resolved['home.stats.label_courses'],
                        'label_certificates' => $resolved['home.stats.label_certificates'],
                        'label_countries' => $resolved['home.stats.label_countries'],
                    ],
                    'cta' => [
                        'title_prefix' => $resolved['home.cta.title_prefix'],
                        'title_highlight' => $resolved['home.cta.title_highlight'],
                        'title_suffix' => $resolved['home.cta.title_suffix'],
                        'subtitle' => $resolved['home.cta.subtitle'],
                        'primary_cta' => $resolved['home.cta.primary_cta'],
                        'secondary_cta' => $resolved['home.cta.secondary_cta'],
                    ],
                    'featured' => [
                        'title' => $resolved['home.featured.title'],
                        'subtitle' => $resolved['home.featured.subtitle'],
                        'button_text' => $resolved['home.featured.button_text'],
                    ],
                    'webinar' => [
                        'badge' => $resolved['home.webinar.badge'],
                        'title' => $resolved['home.webinar.title'],
                        'subtitle' => $resolved['home.webinar.subtitle'],
                        'tab_upcoming' => $resolved['home.webinar.tab_upcoming'],
                        'tab_completed' => $resolved['home.webinar.tab_completed'],
                        'button_detail' => $resolved['home.webinar.button_detail'],
                        'button_view_all' => $resolved['home.webinar.button_view_all'],
                    ],
                    'affiliate' => [
                        'title' => $resolved['home.affiliate.title'],
                        'description' => $resolved['home.affiliate.description'],
                        'button_text' => $resolved['home.affiliate.button_text'],
                    ],
                ],
                'footer' => [
                    'description' => $resolved['footer.description'],
                    'copyright_text' => $resolved['footer.copyright_text'],
                    'tagline' => $resolved['footer.tagline'],
                    'show_social_links' => (bool) $resolved['footer.show_social_links'],
                    'links' => json_decode($resolved['footer.links'], true) ?: [],
                ],
                'custom_code' => [
                    'head_scripts' => $resolved['custom_code.head_scripts'],
                    'body_start_scripts' => $resolved['custom_code.body_start_scripts'],
                    'body_end_scripts' => $resolved['custom_code.body_end_scripts'],
                    'custom_css' => $resolved['custom_code.custom_css'],
                ],
            ],
        ]);
    }

    /**
     * Get about page settings
     */
    public function aboutPage()
    {
        $defaults = [
            'about.hero.name' => 'Phan Anh Chiến',
            'about.hero.title' => 'TikTok Marketing Expert & Founder of Sonet',
            'about.hero.subtitle' => 'Đào tạo hơn 10,000+ học viên kiếm tiền từ TikTok',
            'about.hero.avatar_url' => '',
            'about.hero.cover_url' => '',
            'about.hero.verified' => true,
            'about.stats.followers' => '500K+',
            'about.stats.students' => '10,000+',
            'about.stats.courses' => '15+',
            'about.stats.experience' => '5+ năm',
            'about.social.tiktok' => 'https://tiktok.com/@phananhlien',
            'about.social.youtube' => 'https://youtube.com/@phananhlien',
            'about.social.facebook' => 'https://facebook.com/phananhlien',
            'about.social.instagram' => 'https://instagram.com/phananhlien',
            'about.about.headline' => 'Từ 0 follower đến Top Creator TikTok Việt Nam',
            'about.about.bio' => "Xin chào! Mình là Phan Anh Chiến - người sáng lập Sonet và là một trong những TikTok Creator hàng đầu Việt Nam.\n\nVới hơn 5 năm kinh nghiệm trong lĩnh vực Marketing trên mạng xã hội, mình đã giúp hàng ngàn học viên xây dựng thương hiệu cá nhân và kiếm tiền bền vững từ TikTok.\n\nTriết lý đào tạo của mình rất đơn giản: \"Học đi đôi với hành\" - Mỗi khóa học đều được thiết kế với những bài tập thực chiến, case study thực tế và hỗ trợ 1-1 để đảm bảo học viên có thể áp dụng ngay những gì đã học.",
            'about.about.mission' => 'Sứ mệnh của Sonet là giúp mọi người tận dụng sức mạnh của mạng xã hội để phát triển sự nghiệp và thu nhập thụ động.',
            'about.achievements' => json_encode([
                ['icon' => 'trophy', 'title' => 'Top 100 TikTok Creator', 'description' => 'Được TikTok Việt Nam công nhận năm 2023'],
                ['icon' => 'users', 'title' => '10,000+ Học viên', 'description' => 'Đã đào tạo thành công trên toàn quốc'],
                ['icon' => 'trending', 'title' => '500+ Triệu Views', 'description' => 'Tổng lượt xem video trên các nền tảng'],
                ['icon' => 'award', 'title' => 'Speaker tại các sự kiện', 'description' => 'VietnamWeb Summit, TikTok Creator Day'],
            ]),
            'about.skills' => json_encode([
                ['name' => 'TikTok Marketing', 'level' => 98],
                ['name' => 'Content Creation', 'level' => 95],
                ['name' => 'Personal Branding', 'level' => 92],
                ['name' => 'Video Editing', 'level' => 88],
                ['name' => 'Affiliate Marketing', 'level' => 90],
            ]),
            'about.testimonials' => json_encode([
                ['name' => 'Nguyễn Thị Hồng', 'avatar' => '', 'role' => 'TikToker 200K followers', 'content' => 'Nhờ khóa học của anh Chiến, mình đã từ 0 lên 200K followers chỉ trong 3 tháng. Các kiến thức rất thực tế và dễ áp dụng!', 'rating' => 5],
                ['name' => 'Trần Văn Minh', 'avatar' => '', 'role' => 'Shop owner trên TikTok', 'content' => 'Doanh thu shop mình tăng 300% sau khi học xong khóa TikTok Shop của anh Chiến. Highly recommend!', 'rating' => 5],
                ['name' => 'Lê Thị Mai', 'avatar' => '', 'role' => 'Content Creator', 'content' => 'Anh Chiến không chỉ dạy kiến thức mà còn truyền cảm hứng. Mình đã thay đổi hoàn toàn mindset về việc làm content.', 'rating' => 5],
            ]),
            'about.cta.title' => 'Sẵn sàng bắt đầu hành trình?',
            'about.cta.subtitle' => 'Tham gia cùng 10,000+ học viên đã thành công với TikTok',
            'about.cta.button_text' => 'Xem các khóa học',
            'about.cta.button_url' => '/courses',
        ];

        $resolved = [];
        foreach ($defaults as $key => $default) {
            $resolved[$key] = SettingsService::get($key, $default);
        }

        return response()->json([
            'settings' => [
                'hero' => [
                    'name' => $resolved['about.hero.name'],
                    'title' => $resolved['about.hero.title'],
                    'subtitle' => $resolved['about.hero.subtitle'],
                    'avatar_url' => $resolved['about.hero.avatar_url'],
                    'cover_url' => $resolved['about.hero.cover_url'],
                    'verified' => (bool) $resolved['about.hero.verified'],
                ],
                'stats' => [
                    'followers' => $resolved['about.stats.followers'],
                    'students' => $resolved['about.stats.students'],
                    'courses' => $resolved['about.stats.courses'],
                    'experience' => $resolved['about.stats.experience'],
                ],
                'social' => [
                    'tiktok' => $resolved['about.social.tiktok'],
                    'youtube' => $resolved['about.social.youtube'],
                    'facebook' => $resolved['about.social.facebook'],
                    'instagram' => $resolved['about.social.instagram'],
                ],
                'about' => [
                    'headline' => $resolved['about.about.headline'],
                    'bio' => $resolved['about.about.bio'],
                    'mission' => $resolved['about.about.mission'],
                ],
                'achievements' => json_decode($resolved['about.achievements'], true) ?: [],
                'skills' => json_decode($resolved['about.skills'], true) ?: [],
                'testimonials' => json_decode($resolved['about.testimonials'], true) ?: [],
                'cta' => [
                    'title' => $resolved['about.cta.title'],
                    'subtitle' => $resolved['about.cta.subtitle'],
                    'button_text' => $resolved['about.cta.button_text'],
                    'button_url' => $resolved['about.cta.button_url'],
                ],
            ],
        ]);
    }

    /**
     * Get content pages settings
     */
    public function pages()
    {
        $defaults = [
            'pages.support.title' => 'Trung tâm hỗ trợ',
            'pages.support.subtitle' => 'Tìm câu trả lời nhanh cho các vấn đề thường gặp.',
            'pages.support.content' => '<p>Chúng tôi luôn sẵn sàng hỗ trợ bạn qua các kênh chính thức.</p>',
            'pages.faq.title' => 'Câu hỏi thường gặp',
            'pages.faq.subtitle' => 'Những thắc mắc phổ biến từ học viên.',
            'pages.faq.content' => '<p>Tổng hợp các câu hỏi thường gặp từ học viên và cộng đồng.</p>',
            'pages.feedback.title' => 'Góp ý',
            'pages.feedback.subtitle' => 'Chia sẻ ý kiến để chúng tôi cải thiện dịch vụ.',
            'pages.feedback.content' => '<p>Mọi góp ý của bạn đều giúp Sonet phát triển tốt hơn.</p>',
            'pages.terms.title' => 'Điều khoản sử dụng',
            'pages.terms.subtitle' => 'Vui lòng đọc kỹ trước khi sử dụng dịch vụ.',
            'pages.terms.content' => '<p>Nội dung điều khoản sử dụng sẽ được cập nhật tại đây.</p>',
            'pages.privacy.title' => 'Chính sách bảo mật',
            'pages.privacy.subtitle' => 'Cam kết bảo vệ dữ liệu và quyền riêng tư của bạn.',
            'pages.privacy.content' => '<p>Chúng tôi tôn trọng và bảo vệ thông tin cá nhân của người dùng.</p>',
            'pages.refund.title' => 'Chính sách hoàn tiền',
            'pages.refund.subtitle' => 'Quy định hoàn tiền và hỗ trợ thanh toán.',
            'pages.refund.content' => '<p>Vui lòng liên hệ bộ phận hỗ trợ để được hướng dẫn hoàn tiền.</p>',
        ];

        $resolved = [];
        foreach ($defaults as $key => $default) {
            $resolved[$key] = SettingsService::get($key, $default);
        }

        return response()->json([
            'pages' => [
                'support' => [
                    'title' => $resolved['pages.support.title'],
                    'subtitle' => $resolved['pages.support.subtitle'],
                    'content' => $resolved['pages.support.content'],
                ],
                'faq' => [
                    'title' => $resolved['pages.faq.title'],
                    'subtitle' => $resolved['pages.faq.subtitle'],
                    'content' => $resolved['pages.faq.content'],
                ],
                'feedback' => [
                    'title' => $resolved['pages.feedback.title'],
                    'subtitle' => $resolved['pages.feedback.subtitle'],
                    'content' => $resolved['pages.feedback.content'],
                ],
                'terms' => [
                    'title' => $resolved['pages.terms.title'],
                    'subtitle' => $resolved['pages.terms.subtitle'],
                    'content' => $resolved['pages.terms.content'],
                ],
                'privacy' => [
                    'title' => $resolved['pages.privacy.title'],
                    'subtitle' => $resolved['pages.privacy.subtitle'],
                    'content' => $resolved['pages.privacy.content'],
                ],
                'refund' => [
                    'title' => $resolved['pages.refund.title'],
                    'subtitle' => $resolved['pages.refund.subtitle'],
                    'content' => $resolved['pages.refund.content'],
                ],
            ],
        ]);
    }

    /**
     * Get contact page settings
     */
    public function contactPage()
    {
        $defaults = [
            'contact_page.title' => 'Liên hệ',
            'contact_page.subtitle' => 'Hãy để lại thông tin, chúng tôi sẽ phản hồi sớm nhất.',
            'contact_page.banner_url' => '',
            'contact_page.map_embed_url' => '',
            'contact_page.working_hours' => 'Thứ 2 - Thứ 6: 8:00 - 18:00',
            'contact_page.form_note' => 'Chúng tôi phản hồi trong vòng 24 giờ làm việc.',
            'site.contact.email' => 'support@sonnet.vn',
            'site.contact.phone' => '+84 123 456 789',
            'site.contact.address' => '123 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh',
        ];

        $resolved = [];
        foreach ($defaults as $key => $default) {
            $resolved[$key] = SettingsService::get($key, $default);
        }

        return response()->json([
            'contact_page' => [
                'title' => $resolved['contact_page.title'],
                'subtitle' => $resolved['contact_page.subtitle'],
                'banner_url' => $resolved['contact_page.banner_url'],
                'map_embed_url' => $resolved['contact_page.map_embed_url'],
                'working_hours' => $resolved['contact_page.working_hours'],
                'form_note' => $resolved['contact_page.form_note'],
            ],
            'contact' => [
                'email' => $resolved['site.contact.email'],
                'phone' => $resolved['site.contact.phone'],
                'address' => $resolved['site.contact.address'],
            ],
        ]);
    }

    /**
     * Debug: Get raw settings from database for troubleshooting
     */
    public function debug()
    {
        $settings = \App\Models\Setting::query()
            ->whereIn('key', [
                'site.name',
                'site.description',
                'site.url',
                'site.logo_url',
                'site.favicon_url',
                'site.contact.email',
                'site.contact.phone',
                'site.contact.address',
                'site.social.facebook',
                'site.social.youtube',
                'site.social.instagram',
                'footer.description',
                'footer.copyright_text',
                'footer.tagline',
            ])
            ->get(['key', 'value', 'type', 'updated_at']);

        return response()->json([
            'raw_settings' => $settings,
            'site_name_from_service' => SettingsService::get('site.name', 'DEFAULT_NOT_FOUND'),
            'site_logo_from_service' => SettingsService::get('site.logo_url', 'DEFAULT_NOT_FOUND'),
            'footer_description_from_service' => SettingsService::get('footer.description', 'DEFAULT_NOT_FOUND'),
        ]);
    }
}
