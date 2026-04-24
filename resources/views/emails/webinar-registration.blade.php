<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đăng ký Webinar</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f7;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7;padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.07);">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#f97316,#ef4444);padding:32px 40px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">🎓 Đăng ký Webinar thành công!</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px 40px;">
                            <p style="margin:0 0 16px;color:#374151;font-size:16px;line-height:1.6;">
                                Xin chào <strong>{{ $user->name }}</strong>,
                            </p>
                            <p style="margin:0 0 24px;color:#374151;font-size:16px;line-height:1.6;">
                                Bạn đã đăng ký thành công webinar sau:
                            </p>

                            <!-- Webinar Info Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#fff7ed;border:1px solid #fed7aa;border-radius:8px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <h2 style="margin:0 0 12px;color:#c2410c;font-size:20px;font-weight:700;">
                                            {{ $webinar->title }}
                                        </h2>
                                        @if($webinar->scheduled_at)
                                        <p style="margin:0 0 8px;color:#6b7280;font-size:14px;">
                                            📅 <strong>Thời gian:</strong> {{ \Carbon\Carbon::parse($webinar->scheduled_at)->format('H:i - d/m/Y') }}
                                        </p>
                                        @endif
                                        @if($webinar->duration_minutes)
                                        <p style="margin:0 0 8px;color:#6b7280;font-size:14px;">
                                            ⏱️ <strong>Thời lượng:</strong> {{ $webinar->duration_minutes }} phút
                                        </p>
                                        @endif
                                        @if($webinar->instructor_name)
                                        <p style="margin:0;color:#6b7280;font-size:14px;">
                                            👨‍🏫 <strong>Diễn giả:</strong> {{ $webinar->instructor_name }}
                                        </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <!-- Zoom Link -->
                            @if($webinar->zoom_link)
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <p style="margin:0 0 12px;color:#1e40af;font-size:14px;font-weight:600;">
                                            🔗 Link tham gia Webinar:
                                        </p>
                                        <a href="{{ $webinar->zoom_link }}" target="_blank" rel="noopener noreferrer"
                                           style="display:inline-block;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;font-size:15px;">
                                            Tham gia Webinar →
                                        </a>
                                        <p style="margin:12px 0 0;color:#6b7280;font-size:12px;">
                                            Hoặc copy link: <a href="{{ $webinar->zoom_link }}" style="color:#2563eb;word-break:break-all;">{{ $webinar->zoom_link }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <p style="margin:0;color:#6b7280;font-size:14px;line-height:1.6;">
                                Hãy tham gia đúng giờ để không bỏ lỡ nội dung quan trọng. Chúc bạn có buổi webinar bổ ích! 🎉
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 40px;background-color:#f9fafb;border-top:1px solid #e5e7eb;text-align:center;">
                            <p style="margin:0;color:#9ca3af;font-size:12px;">
                                Email này được gửi tự động. Vui lòng không trả lời email này.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
