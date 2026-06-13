# MVC Conventions

This project is migrating from legacy page scripts to a strict MVC structure.

## Layers

1. `app/Controllers/*`
Controller = HTTP flow only:
- auth/role checks
- request parsing (`GET/POST/FILES`)
- response (render/redirect)
- call services

2. `app/Services/*`
Service = business rules:
- validations
- orchestration
- transaction boundaries
- calls to models/helpers

3. `app/Models/*`
Model = data access:
- SQL queries
- row mapping / persistence

4. `app/Views/*`
View = presentation only.

## Rules

1. No SQL inside controllers.
2. No business rules inside views.
3. No `require ... _*.logic.php` in new controllers.
4. Keep controllers short and predictable.
5. Single entry point only: `index.php` + `app/routes.php` (no wrapper pages).

## Current Status (new standard applied)

1. `student/orders.php` -> `OrdersController` + `OrderService` + `OrderModel`
2. `student/my_courses.php` -> `MyCoursesController` + `MyCoursesService` + `EnrollmentModel`
3. `teacher/apply.php` -> `ApplyController` + `ApplyService` + models
4. `student/exam.php` -> `ExamController` + `ExamService` + `ExamModel`
5. `student/meetings.php` -> `MeetingsController` + `MeetingsService` + `MeetingsModel`
6. `teacher/meetings.php` -> `MeetingsController` + `MeetingsService` + `TeacherMeetingsModel`
7. `teacher/exams_manage.php` -> `ExamsManageController` + `ExamsManageService` + `TeacherExamsManageModel`
8. `teacher/exams_create.php` -> `ExamsCreateController` + `ExamsCreateService` + `TeacherExamsCreateModel`
9. `teacher/referrals.php` -> `ReferralsController` + `ReferralsService` + `TeacherReferralsModel`
10. `teacher/course_content.php` -> `CourseContentController` + `CourseContentService` + `TeacherCourseContentModel`
11. `teacher/withdrawals.php` -> `WithdrawalsController` + `WithdrawalsService` + `TeacherWithdrawalsModel`
12. `teacher/course_preview.php` -> `CoursePreviewController` + `CoursePreviewService` + `TeacherCoursePreviewModel`
13. `teacher/course_updates.php` -> `CourseUpdatesController` + `CourseUpdatesService` + `TeacherCourseUpdatesModel`
14. `teacher/exam_preview.php` -> `ExamPreviewController` + `ExamPreviewService` + `TeacherExamPreviewModel`
15. `admin/categories.php` -> `CategoriesController` + `CategoriesService` + `AdminCategoriesModel`
16. `admin/teacher_applications.php` -> `TeacherApplicationsController` + `TeacherApplicationsService` + `AdminTeacherApplicationsModel`
18. `admin/site_settings.php` -> `SiteSettingsController` + `SiteSettingsService` + `AdminSiteSettingsModel`
19. `admin/sales.php` -> `SalesController` + `SalesService` + `AdminSalesModel`
20. `teacher/promotions.php` -> `PromotionsController` + `PromotionsService` + `TeacherPromotionsModel`
21. `teacher/course_edit.php` -> `CourseEditController` + `CourseEditService` + `TeacherCourseEditModel`
22. `teacher/courses.php` -> `CoursesController` + `CoursesService` + `TeacherCoursesModel`
23. `teacher/dashboard.php` -> `DashboardController` + `DashboardService` + `TeacherDashboardModel`
24. `student/dashboard.php` -> `DashboardController` + `DashboardService` + `StudentDashboardModel`
25. `admin/courses.php` -> `CoursesController` + `CoursesService` + `AdminCoursesModel`
26. `admin/dashboard.php` -> `DashboardController` + `DashboardService` + `AdminDashboardModel`
27. `admin/login.php` -> `LoginController` + `LoginService` + `AdminLoginModel`
28. `admin/register.php` -> `RegisterController` + `RegisterService` + `AdminRegisterModel`
29. Front-controller routing activated (`index.php` + `RouteDispatcher` + `app/routes.php`), wrapper files removed.
30. `admin/support.php` -> `SupportController` + `SupportService` + `AdminSupportModel`
31. `admin/support_agents.php` -> `SupportAgentsController` + `SupportAgentsService` + `AdminSupportAgentsModel`
32. `admin/course_preview.php` -> `CoursePreviewController` + `CoursePreviewService` + `AdminCoursePreviewModel`
33. `notifications.php` -> `Web\NotificationsController` + `Web\NotificationsService` + `WebNotificationsModel`
34. `support.php` -> `Web\SupportController` + `Web\SupportService`
35. `payment_return.php` -> `Web\PaymentReturnController` + `Web\PaymentReturnService` + `WebPaymentReturnModel`
36. `checkout.php` -> `Web\CheckoutController` + `Web\CheckoutService` + `WebCheckoutModel`
37. `profile.php` -> `Web\ProfileController` + `Web\ProfileService` + `WebProfileModel`
38. `course.php` -> `Web\CourseController` + `Web\CourseService` + `WebCourseModel`
39. `app/Views/student/exam.php` allégée: calculs d'affichage déplacés vers `Student\ExamService` (view-model prêt au rendu)

40. `logout.php` -> `Web\LogoutController` (route `logout`)
41. `share.php` -> `Web\ShareController` + `Web\ShareService` + `WebShareModel`
42. `certificate.php` -> `Web\CertificateController` + `Web\CertificateService` + `WebCertificateModel`
43. `cgu.php` -> `Web\CguController` + `Web\LegalPageService` + `app/Views/web/cgu.php`
44. `privacy.php` -> `Web\PrivacyController` + `Web\LegalPageService` + `app/Views/web/privacy.php`
45. Paiement crypto retiré du parcours web.
46. `support_download.php` -> `Web\SupportDownloadController` + `Web\SupportDownloadService` + `WebSupportDownloadModel`
47. `video_stream.php` -> `Web\VideoStreamController` + `Web\VideoStreamService` + `WebVideoStreamModel`
48. `company/dashboard.php` -> `Company\DashboardController` + `Company\DashboardService` + `CompanyDashboardModel` + `app/Views/company/dashboard.php`
49. `company/invite_accept.php` -> `Company\InviteAcceptController` + `Company\InviteAcceptService` + `CompanyInviteAcceptModel` + `app/Views/company/invite_accept.php`
50. `drive/oauth_connect.php` -> `Drive\OauthConnectController` + `Drive\OauthConnectService`
51. `drive/oauth_callback.php` -> `Drive\OauthCallbackController` + `Drive\OauthCallbackService` + `app/Views/drive/oauth_result.php`
52. `oauth/google.php` -> `Oauth\GoogleController` (route `oauth/google`)
53. `api/courses.php` -> `Api\CoursesController` + `Api\CatalogService` + `ApiCatalogModel`
54. `api/cart.php` -> `Api\CartController` + `Api\CommerceService` + `ApiCommerceModel`
55. `api/rating.php` -> `Api\RatingController` + `Api\CommerceService` + `ApiCommerceModel`
56. `api/referral_validate.php` -> `Api\ReferralValidateController` + `Api\CommerceService` + `ApiCommerceModel`
57. `api/notes.php` -> `Api\NotesController` + `Api\LearningService` + `ApiLearningModel`
58. `api/bookmarks.php` -> `Api\BookmarksController` + `Api\LearningService` + `ApiLearningModel`
59. `api/progress.php` -> `Api\ProgressController` + `Api\LearningService` + `ApiLearningModel`
60. `api/progress_position.php` -> `Api\ProgressPositionController` + `Api\LearningService` + `ApiLearningModel`
61. `api/lesson_complete.php` -> `Api\LessonCompleteController` + `Api\LearningService` + `ApiLearningModel`
62. `api/support_conversations.php` -> `Api\SupportConversationsController` + `Api\SupportService` + `ApiSupportModel`
63. `api/support_messages.php` -> `Api\SupportMessagesController` + `Api\SupportService` + `ApiSupportModel`
64. `api/fedapay_webhook.php` -> `Api\FedapayWebhookController` + `Api\FedapayWebhookService` + `ApiFedapayWebhookModel`
65. `index.php` (home page rendering) -> `Web\HomeController` + `Web\HomeService` + `WebHomeModel` + `app/Views/web/home.php`
66. Core HTTP concerns centralized: `app/Core/Auth.php`, `app/Core/Csrf.php`, `app/Core/Flash.php`, `app/Core/Session.php`; all controllers use base methods (`requireAuth`, `requireCsrf`, `currentUser`, `flashGet/flashSet`) instead of direct global helpers.
67. Service layer unified on `App\Core\Service`: all services now use `$this->flashGet/$this->flashSet/$this->currentUser/$this->loginUser` instead of legacy global auth/session helpers.
68. View CSRF is now explicit through `view_csrf_token()` loaded by `app/Core/View.php` from `app/View/helpers.php` (views no longer call `csrf_token()`).
69. `includes/helpers.php` reduced: legacy auth/csrf/flash wrappers removed; only utility functions and domain helpers remain.
70. `Controllers/Services` now call base methods (`$this->siteUrl()`, `$this->absUrl()`, `$this->sendEmail()`, `$this->siteLogoUrl()`, etc.) instead of direct global helper calls.
71. `app/tests/smoke_routes.php` now enforces an MVC guardrail that fails if forbidden global helper calls reappear in `app/Controllers` or `app/Services`.
72. PDO access is now centralized in `App\Core\PdoRegistry`; base `Controller` and `Service` resolve PDO from this registry (no direct reliance on global scope).
73. `global $pdo` has been removed from all controllers/services; controllers now use `$this->pdo()` and helper factories in `includes/helpers.php` now read PDO from `PdoRegistry`.
74. Legacy PDO bridge `app_pdo()` has been removed from `includes/bootstrap.php`; the unique PDO source is now `App\Core\PdoRegistry`.
75. Legacy schema runtime helpers (`ensure_*`, `*_has_*`) are now centralized in `App\Core\SchemaGuard`; controllers/services must call `$this->schema()->...` instead of global helper calls.
76. `app/tests/smoke_routes.php` now also fails when a controller/service contains direct `ensure_*` or `*_has_*` calls.
77. Runtime schema DDL (`ALTER TABLE`, `CREATE TABLE IF NOT EXISTS`) has been removed from `app/Models/*` and `includes/helpers.php`; schema changes must come from `database/schema_v*.sql` migrations.
78. `app/tests/smoke_routes.php` now fails if runtime DDL patterns are found in `app/Models` or `includes/helpers.php`.
79. Use `php app/tests/schema_coverage.php` before production to verify required tables/columns/indexes are present after migrations.

Remaining migrated pages that still use transitional `*_logic.php` should be moved to the same pattern progressively.
