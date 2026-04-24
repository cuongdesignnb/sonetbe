<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminPageSettingsController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    public function pages()
    {
        $this->ensureAdmin();

        $defaults = $this->pageDefaults();
        $resolved = [];
        foreach ($defaults as $key => $default) {
            $resolved[$key] = SettingsService::get($key, $default);
        }

        return response()->json([
            'pages' => $this->formatPages($resolved),
        ]);
    }

    public function updatePages(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'pages.support.title' => 'nullable|string|max:255',
            'pages.support.subtitle' => 'nullable|string|max:1000',
            'pages.support.content' => 'nullable|string|max:65535',
            'pages.faq.title' => 'nullable|string|max:255',
            'pages.faq.subtitle' => 'nullable|string|max:1000',
            'pages.faq.content' => 'nullable|string|max:65535',
            'pages.feedback.title' => 'nullable|string|max:255',
            'pages.feedback.subtitle' => 'nullable|string|max:1000',
            'pages.feedback.content' => 'nullable|string|max:65535',
            'pages.terms.title' => 'nullable|string|max:255',
            'pages.terms.subtitle' => 'nullable|string|max:1000',
            'pages.terms.content' => 'nullable|string|max:65535',
            'pages.privacy.title' => 'nullable|string|max:255',
            'pages.privacy.subtitle' => 'nullable|string|max:1000',
            'pages.privacy.content' => 'nullable|string|max:65535',
            'pages.refund.title' => 'nullable|string|max:255',
            'pages.refund.subtitle' => 'nullable|string|max:1000',
            'pages.refund.content' => 'nullable|string|max:65535',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $items = [];
        foreach (array_keys($this->pageDefaults()) as $key) {
            $items[] = ['key' => $key, 'value' => data_get($data, $this->keyToPath($key))];
        }

        SettingsService::setMany($items);

        return response()->json(['message' => 'Pages updated']);
    }

    public function contact()
    {
        $this->ensureAdmin();

        $defaults = $this->contactDefaults();
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
        ]);
    }

    public function updateContact(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'contact_page.title' => 'nullable|string|max:255',
            'contact_page.subtitle' => 'nullable|string|max:1000',
            'contact_page.banner_url' => 'nullable|string|max:500',
            'contact_page.map_embed_url' => 'nullable|string|max:2000',
            'contact_page.working_hours' => 'nullable|string|max:255',
            'contact_page.form_note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $items = [];
        foreach (array_keys($this->contactDefaults()) as $key) {
            $items[] = ['key' => $key, 'value' => data_get($data, $this->keyToPath($key))];
        }

        SettingsService::setMany($items);

        return response()->json(['message' => 'Contact page updated']);
    }

    private function pageDefaults(): array
    {
        return [
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
    }

    private function contactDefaults(): array
    {
        return [
            'contact_page.title' => 'Liên hệ',
            'contact_page.subtitle' => 'Hãy để lại thông tin, chúng tôi sẽ phản hồi sớm nhất.',
            'contact_page.banner_url' => '',
            'contact_page.map_embed_url' => '',
            'contact_page.working_hours' => 'Thứ 2 - Thứ 6: 8:00 - 18:00',
            'contact_page.form_note' => 'Chúng tôi phản hồi trong vòng 24 giờ làm việc.',
        ];
    }

    private function formatPages(array $resolved): array
    {
        return [
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
        ];
    }

    private function keyToPath(string $key): string
    {
        return str_replace('.', '.', $key);
    }
}
