<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogCommentController;
use App\Http\Controllers\Admin\AdminCourseController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminSectionController;
use App\Http\Controllers\Admin\AdminLessonController;
use App\Http\Controllers\Admin\AdminMediaController;
use App\Http\Controllers\Admin\AdminCourseFaqController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminBlogCategoryController;
use App\Http\Controllers\Admin\AdminBlogPostController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Admin\AdminEnrollmentController;
use App\Http\Controllers\SePayWebhookController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminBunnyController;
use App\Http\Controllers\Admin\AdminPageSettingsController;
use App\Http\Controllers\Admin\AdminVoucherController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WebinarController;
use App\Http\Controllers\EbookController;
use App\Http\Controllers\Admin\AdminWebinarController;
use App\Http\Controllers\Admin\AdminEbookController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminCourseMarketingController;
use App\Http\Controllers\Admin\AdminMenuController;
use App\Http\Controllers\EbookPaymentController;
use App\Http\Controllers\WebinarPaymentController;
use App\Http\Controllers\InvoiceRequestController;
use App\Http\Controllers\Admin\AdminInvoiceRequestController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Admin\AdminServiceCategoryController;
use App\Http\Controllers\Admin\AdminServicePostController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);

// Public course routes
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::post('/courses/{courseId}/reviews', [ReviewController::class, 'store']);

// Public blog routes
Route::get('/blog', [BlogController::class, 'index']);
Route::get('/blog/debug', [BlogController::class, 'debug']);
Route::get('/blog/categories', [BlogController::class, 'categories']);
Route::get('/blog/{slug}', [BlogController::class, 'show']);
Route::get('/blog/{slug}/comments', [BlogCommentController::class, 'indexBySlug']);

// Public service routes
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/categories', [ServiceController::class, 'categories']);
Route::get('/services/{slug}', [ServiceController::class, 'show']);

// Public settings
Route::get('/settings', [SettingsController::class, 'index']);
Route::get('/settings/about', [SettingsController::class, 'aboutPage']);
Route::get('/settings/pages', [SettingsController::class, 'pages']);
Route::get('/settings/contact-page', [SettingsController::class, 'contactPage']);
Route::get('/settings/debug', [SettingsController::class, 'debug']);

// Public menus (active only, nested tree)
Route::get('/menus', function () {
    return response()->json([
        'menus' => \App\Models\Menu::tree(true),
    ]);
});

// Public FAQs
Route::get('/faqs', [FaqController::class, 'index']);

// Public webinar routes
Route::get('/webinars', [WebinarController::class, 'index']);
Route::get('/webinars/{slug}', [WebinarController::class, 'show']);
Route::post('/webinars/{slug}/guest-register', [WebinarController::class, 'guestRegister']);

// Public ebook routes
Route::get('/ebooks', [EbookController::class, 'index']);
Route::get('/ebooks/{slug}', [EbookController::class, 'show']);

// Public preview video – allows unauthenticated users to watch preview lessons
Route::get('/lessons/{id}/preview-video', [LessonController::class, 'streamPreviewVideo'])
    ->middleware(['block.download.browsers']);

// SePay webhook (public)
Route::post('/sepay/webhook', [SePayWebhookController::class, 'handle']);

// HLS proxy - must be public because video player doesn't send auth headers
// Security is handled via signed URLs with timestamp verification
// ALSO protected by BlockDownloadBrowsers middleware to block Cốc Cốc and download managers
Route::get('/lessons/{id}/hls', [LessonController::class, 'proxyHls'])
    ->name('lesson.hls')
    ->middleware(['block.download.browsers'])
    ->withoutMiddleware(['auth:sanctum', 'auth']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/me', [AuthController::class, 'updateProfile']);
    
    // Category management (for admin/instructors)
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    
    // Course management (for instructors)
    Route::post('/courses', [CourseController::class, 'store']);
    Route::put('/courses/{id}', [CourseController::class, 'update']);
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
    Route::get('/my-courses', [CourseController::class, 'myCourses']);
    
    // Lesson management
    Route::post('/courses/{courseId}/lessons', [LessonController::class, 'store']);
    Route::put('/lessons/{id}', [LessonController::class, 'update']);
    Route::delete('/lessons/{id}', [LessonController::class, 'destroy']);
    Route::post('/lessons/{id}/upload-video', [LessonController::class, 'uploadVideo']);

    // Blog comments
    Route::post('/blog/{slug}/comments', [BlogCommentController::class, 'storeBySlug']);
    
    // Voucher validation
    Route::post('/vouchers/validate', [VoucherController::class, 'validateVoucher']);
    
    // Webinar registration
    Route::post('/webinars/{slug}/register', [WebinarController::class, 'register']);
    Route::post('/webinars/{slug}/cancel-registration', [WebinarController::class, 'cancelRegistration']);
    
    // Webinar payment (paid webinars)
    Route::post('/webinars/{slug}/checkout', [WebinarPaymentController::class, 'checkout']);
    Route::get('/webinar-payments/{id}/status', [WebinarPaymentController::class, 'status']);
    
    // Enrollment routes
    Route::post('/courses/{courseId}/enroll', [EnrollmentController::class, 'enroll']);
    Route::post('/courses/{courseId}/checkout', [PaymentController::class, 'checkout']);
    Route::get('/my-enrollments', [EnrollmentController::class, 'myEnrollments']);
    Route::get('/payments/{id}/status', [PaymentController::class, 'status']);

    // Ebook purchase
    Route::post('/ebooks/{ebookId}/checkout', [EbookPaymentController::class, 'checkout']);
    Route::get('/ebook-payments/{id}/status', [EbookPaymentController::class, 'status']);

    // Invoice requests
    Route::post('/invoice-requests', [InvoiceRequestController::class, 'store']);
    Route::get('/invoice-requests/payment/{paymentId}', [InvoiceRequestController::class, 'showByPayment']);

    Route::get('/courses/{courseId}/progress', [EnrollmentController::class, 'getCourseProgress']);
    Route::post('/lessons/{lessonId}/progress', [EnrollmentController::class, 'updateLessonProgress']);
    Route::post('/lessons/{lessonId}/duration', [LessonController::class, 'updateDuration']);
    
    // Video streaming (requires auth) - Protected by BlockDownloadBrowsers
    Route::get('/lessons/{id}/video', [LessonController::class, 'streamVideo'])
        ->name('lesson.video')
        ->middleware(['block.download.browsers']);
    
    // Dashboard data
    Route::get('/dashboard/stats', function (Request $request) {
        $user = $request->user();
        
        if ($user->isInstructor()) {
            // Instructor dashboard
            $stats = [
                'total_courses' => $user->instructorCourses()->count(),
                'total_students' => $user->instructorCourses()
                    ->withCount('enrollments')
                    ->get()
                    ->sum('enrollments_count'),
                'total_revenue' => $user->instructorCourses()
                    ->join('enrollments', 'courses.id', '=', 'enrollments.course_id')
                    ->where('enrollments.status', 'active')
                    ->sum('enrollments.amount_paid')
            ];
        } else {
            // Student dashboard
            $stats = [
                'enrolled_courses' => $user->enrollments()->where('status', 'active')->count(),
                'completed_courses' => $user->enrollments()->where('status', 'completed')->count(),
                'total_learning_time' => $user->lessonProgress()->sum('watched_duration')
            ];
        }
        
        return response()->json($stats);
    });

    // Admin routes
    Route::prefix('admin')->group(function () {
        // Categories
        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::get('/categories/{id}', [AdminCategoryController::class, 'show']);
        Route::put('/categories/{id}', [AdminCategoryController::class, 'update']);
        Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy']);

        // Courses
        Route::get('/courses', [AdminCourseController::class, 'index']);
        Route::get('/courses/options', [AdminCourseController::class, 'options']);
        Route::post('/courses', [AdminCourseController::class, 'store']);
        Route::post('/courses/reorder', [AdminCourseController::class, 'reorder']);
        Route::post('/courses/{id}/duplicate', [AdminCourseController::class, 'duplicate']);
        Route::get('/courses/{id}', [AdminCourseController::class, 'show']);
        Route::put('/courses/{id}', [AdminCourseController::class, 'update']);
        Route::delete('/courses/{id}', [AdminCourseController::class, 'destroy']);

        // Course Marketing (Landing Page)
        Route::get('/courses/{id}/marketing', [AdminCourseMarketingController::class, 'show']);
        Route::put('/courses/{id}/marketing', [AdminCourseMarketingController::class, 'update']);
        Route::patch('/courses/{id}/marketing', [AdminCourseMarketingController::class, 'patch']);
        Route::get('/courses/{id}/marketing/{section}', [AdminCourseMarketingController::class, 'showSection']);
        Route::put('/courses/{id}/marketing/{section}', [AdminCourseMarketingController::class, 'updateSection']);
        Route::delete('/courses/{id}/marketing/{section}', [AdminCourseMarketingController::class, 'deleteSection']);

        // FAQs
        Route::get('/faqs', [AdminCourseFaqController::class, 'index']);
        Route::post('/faqs', [AdminCourseFaqController::class, 'store']);
        Route::put('/faqs/{id}', [AdminCourseFaqController::class, 'update']);
        Route::delete('/faqs/{id}', [AdminCourseFaqController::class, 'destroy']);

        // Reviews
        Route::get('/reviews', [AdminReviewController::class, 'index']);
        Route::put('/reviews/{id}', [AdminReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);

        // Blog Categories
        Route::get('/blog-categories', [AdminBlogCategoryController::class, 'index']);
        Route::get('/blog-categories/{id}', [AdminBlogCategoryController::class, 'show']);
        Route::post('/blog-categories', [AdminBlogCategoryController::class, 'store']);
        Route::put('/blog-categories/{id}', [AdminBlogCategoryController::class, 'update']);
        Route::delete('/blog-categories/{id}', [AdminBlogCategoryController::class, 'destroy']);

        // Blog Posts
        Route::get('/blog-posts', [AdminBlogPostController::class, 'index']);
        Route::get('/blog-posts/{id}', [AdminBlogPostController::class, 'show']);
        Route::post('/blog-posts', [AdminBlogPostController::class, 'store']);
        Route::put('/blog-posts/{id}', [AdminBlogPostController::class, 'update']);
        Route::delete('/blog-posts/{id}', [AdminBlogPostController::class, 'destroy']);

        // Service Categories
        Route::get('/service-categories', [AdminServiceCategoryController::class, 'index']);
        Route::get('/service-categories/{id}', [AdminServiceCategoryController::class, 'show']);
        Route::post('/service-categories', [AdminServiceCategoryController::class, 'store']);
        Route::put('/service-categories/{id}', [AdminServiceCategoryController::class, 'update']);
        Route::delete('/service-categories/{id}', [AdminServiceCategoryController::class, 'destroy']);

        // Service Posts
        Route::get('/service-posts', [AdminServicePostController::class, 'index']);
        Route::get('/service-posts/{id}', [AdminServicePostController::class, 'show']);
        Route::post('/service-posts', [AdminServicePostController::class, 'store']);
        Route::put('/service-posts/{id}', [AdminServicePostController::class, 'update']);
        Route::delete('/service-posts/{id}', [AdminServicePostController::class, 'destroy']);

        // Users
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/export', [AdminUserController::class, 'export']);
        Route::get('/users/{id}', [AdminUserController::class, 'show']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

        // Enrollments
        Route::get('/enrollments', [AdminEnrollmentController::class, 'index']);
        Route::post('/enrollments/manual', [AdminEnrollmentController::class, 'manualEnroll']);
        Route::get('/dashboard/stats', [AdminEnrollmentController::class, 'stats']);

        // Vouchers
        Route::get('/vouchers', [AdminVoucherController::class, 'index']);
        Route::get('/vouchers/stats', [AdminVoucherController::class, 'stats']);
        Route::post('/vouchers', [AdminVoucherController::class, 'store']);
        Route::get('/vouchers/{id}', [AdminVoucherController::class, 'show']);
        Route::put('/vouchers/{id}', [AdminVoucherController::class, 'update']);
        Route::delete('/vouchers/{id}', [AdminVoucherController::class, 'destroy']);

        // Menus
        Route::get('/menus', [AdminMenuController::class, 'index']);
        Route::post('/menus', [AdminMenuController::class, 'store']);
        Route::post('/menus/reorder', [AdminMenuController::class, 'reorder']);
        Route::put('/menus/{id}', [AdminMenuController::class, 'update']);
        Route::delete('/menus/{id}', [AdminMenuController::class, 'destroy']);

        // Settings
        Route::get('/settings', [AdminSettingsController::class, 'index']);
        Route::put('/settings', [AdminSettingsController::class, 'update']);
        Route::post('/settings/test-email', [AdminSettingsController::class, 'testEmail']);

        // Page content settings
        Route::get('/pages', [AdminPageSettingsController::class, 'pages']);
        Route::put('/pages', [AdminPageSettingsController::class, 'updatePages']);
        Route::get('/contact-page', [AdminPageSettingsController::class, 'contact']);
        Route::put('/contact-page', [AdminPageSettingsController::class, 'updateContact']);

        // Sections
        Route::get('/courses/{courseId}/sections', [AdminSectionController::class, 'index']);
        Route::post('/courses/{courseId}/sections', [AdminSectionController::class, 'store']);
        Route::put('/sections/{id}', [AdminSectionController::class, 'update']);
        Route::delete('/sections/{id}', [AdminSectionController::class, 'destroy']);
        Route::post('/courses/{courseId}/sections/reorder', [AdminSectionController::class, 'reorder']);

        // Lessons
        Route::get('/courses/{courseId}/lessons', [AdminLessonController::class, 'index']);
        Route::post('/courses/{courseId}/lessons', [AdminLessonController::class, 'store']);
        Route::put('/lessons/{id}', [AdminLessonController::class, 'update']);
        Route::delete('/lessons/{id}', [AdminLessonController::class, 'destroy']);

        // Media Library - Folders
        Route::get('/media/folders', [AdminMediaController::class, 'folders']);
        Route::post('/media/folders', [AdminMediaController::class, 'createFolder']);
        Route::put('/media/folders/{id}', [AdminMediaController::class, 'updateFolder']);
        Route::delete('/media/folders/{id}', [AdminMediaController::class, 'deleteFolder']);

        // Media Library - Assets
        Route::get('/media', [AdminMediaController::class, 'index']);
        Route::post('/media/images', [AdminMediaController::class, 'uploadImage']);
        Route::put('/media/{id}', [AdminMediaController::class, 'updateAsset']);
        Route::delete('/media/{id}', [AdminMediaController::class, 'deleteAsset']);

        // Bunny Stream
        Route::get('/bunny/libraries', [AdminBunnyController::class, 'libraries']);
        Route::get('/bunny/libraries/{libraryId}/videos', [AdminBunnyController::class, 'videos']);

        // Webinars
        Route::get('/webinars', [AdminWebinarController::class, 'index']);
        Route::post('/webinars', [AdminWebinarController::class, 'store']);
        Route::get('/webinars/{id}', [AdminWebinarController::class, 'show']);
        Route::put('/webinars/{id}', [AdminWebinarController::class, 'update']);
        Route::delete('/webinars/{id}', [AdminWebinarController::class, 'destroy']);
        Route::get('/webinars/{id}/registrations', [AdminWebinarController::class, 'registrations']);

        // Webinar Registrations (all)
        Route::get('/webinar-registrations', [AdminWebinarController::class, 'allRegistrations']);
        Route::put('/webinar-registrations/{id}', [AdminWebinarController::class, 'updateRegistration']);
        Route::delete('/webinar-registrations/{id}', [AdminWebinarController::class, 'deleteRegistration']);

        // Ebooks
        Route::get('/ebooks', [AdminEbookController::class, 'index']);
        Route::post('/ebooks', [AdminEbookController::class, 'store']);
        Route::get('/ebooks/{id}', [AdminEbookController::class, 'show']);
        Route::put('/ebooks/{id}', [AdminEbookController::class, 'update']);
        Route::delete('/ebooks/{id}', [AdminEbookController::class, 'destroy']);

        // Orders (all product types)
        Route::get('/orders', [AdminOrderController::class, 'index']);

        // Invoice Requests
        Route::get('/invoice-requests', [AdminInvoiceRequestController::class, 'index']);
        Route::put('/invoice-requests/{id}', [AdminInvoiceRequestController::class, 'update']);
        Route::delete('/invoice-requests/{id}', [AdminInvoiceRequestController::class, 'destroy']);
    });
});
