<?php
declare(strict_types=1);

use App\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Controllers\Admin\Auth\RegisterController as AdminRegisterController;
use App\Controllers\Admin\Auth\SecondaryLoginController as AdminSecondaryLoginController;
use App\Controllers\Api\BookmarksController as ApiBookmarksController;
use App\Controllers\Api\CartController as ApiCartController;
use App\Controllers\Api\CatalogEventsController as ApiCatalogEventsController;
use App\Controllers\Api\CourseFavoritesController as ApiCourseFavoritesController;
use App\Controllers\Api\CoursesController as ApiCoursesController;
use App\Controllers\Api\FedapayWebhookController as ApiFedapayWebhookController;
use App\Controllers\Api\LessonCompleteController as ApiLessonCompleteController;
use App\Controllers\Api\NotificationsFeedController as ApiNotificationsFeedController;
use App\Controllers\Api\NotesController as ApiNotesController;
use App\Controllers\Api\PushSubscriptionController as ApiPushSubscriptionController;
use App\Controllers\Api\ProgressController as ApiProgressController;
use App\Controllers\Api\ProgressPositionController as ApiProgressPositionController;
use App\Controllers\Api\RatingController as ApiRatingController;
use App\Controllers\Api\ReferralValidateController as ApiReferralValidateController;
use App\Controllers\Api\SupportConversationsController as ApiSupportConversationsController;
use App\Controllers\Api\SupportMessagesController as ApiSupportMessagesController;
use App\Controllers\Admin\CategoriesController;
use App\Controllers\Admin\CertificatePreviewController as AdminCertificatePreviewController;
use App\Controllers\Admin\CoursePreviewController as AdminCoursePreviewController;
use App\Controllers\Admin\CoursesController;
use App\Controllers\Admin\CourseInsightsController as AdminCourseInsightsController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\AnnouncementsController as AdminAnnouncementsController;
use App\Controllers\Admin\ErrorLogsController;
use App\Controllers\Admin\NotificationsController as AdminNotificationsController;
use App\Controllers\Admin\SalesController;
use App\Controllers\Admin\SecondaryAdminsController;
use App\Controllers\Admin\SiteSettingsController;
use App\Controllers\Admin\StatisticsController as AdminStatisticsController;
use App\Controllers\Admin\SupportAgentsController;
use App\Controllers\Admin\SupportController;
use App\Controllers\Admin\TeacherApplicationsController;
use App\Controllers\Auth\ForgotController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\Auth\ResetController;
use App\Controllers\Auth\VerifyController;
use App\Controllers\Company\Auth\SsoController as CompanySsoController;
use App\Controllers\Company\DashboardController as CompanyDashboardController;
use App\Controllers\Company\AnalyticsController as CompanyAnalyticsController;
use App\Controllers\Company\BillingController as CompanyBillingController;
use App\Controllers\Company\HelpController as CompanyHelpController;
use App\Controllers\Company\InvitesController as CompanyInvitesController;
use App\Controllers\Company\MembersController as CompanyMembersController;
use App\Controllers\Company\OnboardingController as CompanyOnboardingController;
use App\Controllers\Company\OpsController as CompanyOpsController;
use App\Controllers\Company\SettingsController as CompanySettingsController;
use App\Controllers\Company\SeatsController as CompanySeatsController;
use App\Controllers\Company\SuccessReviewsController as CompanySuccessReviewsController;
use App\Controllers\Company\TeamsController as CompanyTeamsController;
use App\Controllers\Company\InviteAcceptController as CompanyInviteAcceptController;
use App\Controllers\Company\Auth\RegisterController as CompanyRegisterController;
use App\Controllers\Drive\OauthCallbackController as DriveOauthCallbackController;
use App\Controllers\Drive\OauthConnectController as DriveOauthConnectController;
use App\Controllers\Oauth\GoogleController as OauthGoogleController;
use App\Controllers\Student\DashboardController as StudentDashboardController;
use App\Controllers\Student\ExamController;
use App\Controllers\Student\CertificatesController as StudentCertificatesController;
use App\Controllers\Student\FavoritesController as StudentFavoritesController;
use App\Controllers\Student\MeetingsController as StudentMeetingsController;
use App\Controllers\Student\MyCoursesController;
use App\Controllers\Student\OrdersController;
use App\Controllers\Student\ReferralsController as StudentReferralsController;
use App\Controllers\Teacher\ApplyController;
use App\Controllers\Teacher\CouponsController;
use App\Controllers\Teacher\CourseContentController;
use App\Controllers\Teacher\CourseCreateController;
use App\Controllers\Teacher\CourseActionsController;
use App\Controllers\Teacher\CourseEditController;
use App\Controllers\Teacher\CoursePreviewController;
use App\Controllers\Teacher\CoursesController as TeacherCoursesController;
use App\Controllers\Teacher\CourseUpdatesController;
use App\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Controllers\Teacher\ExamPreviewController;
use App\Controllers\Teacher\ExamsCreateController;
use App\Controllers\Teacher\ExamsManageController;
use App\Controllers\Teacher\MeetingsController as TeacherMeetingsController;
use App\Controllers\Teacher\PromotionsController;
use App\Controllers\Teacher\ReferralsController;
use App\Controllers\Teacher\ReviewsController as TeacherReviewsController;
use App\Controllers\Teacher\SignatureController as TeacherSignatureController;
use App\Controllers\Teacher\WithdrawalsController;
use App\Controllers\Web\CheckoutController;
use App\Controllers\Web\CertificateController;
use App\Controllers\Web\CguController;
use App\Controllers\Web\CatalogueController;
use App\Controllers\Web\CourseController;
use App\Controllers\Web\CourseWatchController;
use App\Controllers\Web\CoursePreviewOpenController;
use App\Controllers\Web\HomeController;
use App\Controllers\Web\LogoutController;
use App\Controllers\Web\NotificationsController;
use App\Controllers\Web\PaymentReturnController;
use App\Controllers\Web\PrivacyController;
use App\Controllers\Web\ProfileController;
use App\Controllers\Web\RobotsController;
use App\Controllers\Web\ServiceWorkerController;
use App\Controllers\Web\ShareController;
use App\Controllers\Web\SitemapController;
use App\Controllers\Web\SupportDownloadController;
use App\Controllers\Web\SupportController as WebSupportController;
use App\Controllers\Web\VideoStreamController;

$routes = [
    '' => HomeController::class,
    'catalogue' => CatalogueController::class,
    'course' => CourseController::class,
    'course/watch' => CourseWatchController::class,
    'student/course/watch' => CourseWatchController::class,
    'promotions' => PromotionsController::class,
    'course_preview_open' => CoursePreviewOpenController::class,
    'checkout' => CheckoutController::class,
    'certificate' => CertificateController::class,
    'cgu' => CguController::class,
    'privacy' => PrivacyController::class,
    'profile' => ProfileController::class,
    'share' => ShareController::class,
    'notifications' => NotificationsController::class,
    'logout' => LogoutController::class,
    'support' => WebSupportController::class,
    'support_download' => SupportDownloadController::class,
    'video_stream' => VideoStreamController::class,
    'payment_return' => PaymentReturnController::class,
    'service_worker.js' => ServiceWorkerController::class,
    'robots.txt' => RobotsController::class,
    'sitemap.xml' => SitemapController::class,

    'login' => LoginController::class,
    'register' => RegisterController::class,
    'forgot' => ForgotController::class,
    'reset' => ResetController::class,
    'verify' => VerifyController::class,
    'oauth/google' => OauthGoogleController::class,
    'company/register' => CompanyRegisterController::class,
    'company/sso' => CompanySsoController::class,
    'company/dashboard' => CompanyDashboardController::class,
    'company/analytics' => CompanyAnalyticsController::class,
    'company/billing' => CompanyBillingController::class,
    'company/invites' => CompanyInvitesController::class,
    'company/members' => CompanyMembersController::class,
    'company/onboarding' => CompanyOnboardingController::class,
    'company/ops' => CompanyOpsController::class,
    'company/reviews' => CompanySuccessReviewsController::class,
    'company/settings' => CompanySettingsController::class,
    'company/seats' => CompanySeatsController::class,
    'company/teams' => CompanyTeamsController::class,
    'company/help' => CompanyHelpController::class,
    'company/invite_accept' => CompanyInviteAcceptController::class,
    'drive/oauth_connect' => DriveOauthConnectController::class,
    'drive/oauth_callback' => DriveOauthCallbackController::class,

    'api/courses' => ApiCoursesController::class,
    'api/catalog_events' => ApiCatalogEventsController::class,
    'api/cart' => ApiCartController::class,
    'api/course_favorites' => ApiCourseFavoritesController::class,
    'api/rating' => ApiRatingController::class,
    'api/notes' => ApiNotesController::class,
    'api/bookmarks' => ApiBookmarksController::class,
    'api/progress' => ApiProgressController::class,
    'api/progress_position' => ApiProgressPositionController::class,
    'api/lesson_complete' => ApiLessonCompleteController::class,
    'api/notifications_feed' => ApiNotificationsFeedController::class,
    'api/push_subscription' => ApiPushSubscriptionController::class,
    'api/referral_validate' => ApiReferralValidateController::class,
    'api/support_conversations' => ApiSupportConversationsController::class,
    'api/support_messages' => ApiSupportMessagesController::class,
    'api/fedapay_webhook' => ApiFedapayWebhookController::class,

    'admin/login' => AdminLoginController::class,
    'admin/secondary_login' => AdminSecondaryLoginController::class,
    'admin/dashboard' => AdminDashboardController::class,
    'admin/statistics' => AdminStatisticsController::class,
    'admin/error_logs' => ErrorLogsController::class,
    'admin/notifications' => AdminNotificationsController::class,
    'admin/announcements/create' => AdminAnnouncementsController::class,
    'admin/announcements' => AdminAnnouncementsController::class,
    'admin/courses' => CoursesController::class,
    'admin/course_insights' => AdminCourseInsightsController::class,
    'admin/course_preview' => AdminCoursePreviewController::class,
    'admin/categories' => CategoriesController::class,
    'admin/certificate_preview' => AdminCertificatePreviewController::class,
    'admin/sales' => SalesController::class,
    'admin/site_settings' => SiteSettingsController::class,
    'admin/teacher_applications' => TeacherApplicationsController::class,
    'admin/support' => SupportController::class,
    'admin/support_agents' => SupportAgentsController::class,
    'admin/secondary_admins' => SecondaryAdminsController::class,

    'student/dashboard' => StudentDashboardController::class,
    'student/certificates' => StudentCertificatesController::class,
    'student/exam' => ExamController::class,
    'student/favorites' => StudentFavoritesController::class,
    'student/meetings' => StudentMeetingsController::class,
    'student/my_courses' => MyCoursesController::class,
    'student/orders' => OrdersController::class,
    'student/referrals' => StudentReferralsController::class,
    'student/referrals/filleuls' => StudentReferralsController::class,
    'student/referrals/gains' => StudentReferralsController::class,
    'student/affiliation' => StudentReferralsController::class,
    'student/affiliation/filleuls' => StudentReferralsController::class,
    'student/affiliation/gains' => StudentReferralsController::class,

    'teacher/apply' => ApplyController::class,
    'teacher/coupons' => CouponsController::class,
    'teacher/courses' => TeacherCoursesController::class,
    'teacher/course_actions' => CourseActionsController::class,
    'teacher/course_create' => CourseCreateController::class,
    'teacher/course_content' => CourseContentController::class,
    'teacher/course_edit' => CourseEditController::class,
    'teacher/course_preview' => CoursePreviewController::class,
    'teacher/course_updates' => CourseUpdatesController::class,
    'teacher/dashboard' => TeacherDashboardController::class,
    'teacher/exams_create' => ExamsCreateController::class,
    'teacher/exams_manage' => ExamsManageController::class,
    'teacher/exam_preview' => ExamPreviewController::class,
    'teacher/meetings' => TeacherMeetingsController::class,
    'teacher/reviews' => TeacherReviewsController::class,
    'teacher/signature' => TeacherSignatureController::class,
    'teacher/referrals' => ReferralsController::class,
    'teacher/referrals/filleuls' => ReferralsController::class,
    'teacher/affiliation' => ReferralsController::class,
    'teacher/affiliation/filleuls' => ReferralsController::class,
    'teacher/withdrawals' => WithdrawalsController::class,
    'teacher/withdrawals/revenus' => WithdrawalsController::class,
    'teacher/withdrawals/transactions' => WithdrawalsController::class,
    'teacher/withdrawals/retraits' => WithdrawalsController::class,
    'teacher/promotions' => PromotionsController::class,
];

if (getenv('ADMIN_BOOTSTRAP_REGISTER') === '1' && function_exists('is_local_environment') && is_local_environment()) {
    $routes['admin/register'] = AdminRegisterController::class;
}

return $routes;
