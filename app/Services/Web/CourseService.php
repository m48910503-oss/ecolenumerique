<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;

use App\Models\ApiCatalogModel;
use App\Models\WebCourseModel;
use App\Models\WebProfileModel;
use App\Services\Shared\PromotionsService;
use App\Services\Shared\ReferralService;
use DateTime;
use Throwable;

final class CourseService extends Service
{
    private WebCourseModel $model;
    private ReferralService $referrals;
    private PromotionsService $promotions;
    private PreviewTokenService $previewTokens;

    public function __construct(
        WebCourseModel $model,
        ReferralService $referrals,
        PromotionsService $promotions,
        ?PreviewTokenService $previewTokens = null
    )
    {
        $this->model = $model;
        $this->referrals = $referrals;
        $this->promotions = $promotions;
        $this->previewTokens = $previewTokens ?? new PreviewTokenService();
    }

    /**
     * @return array{httpStatus:?int,abortTitle:?string,abortMessage:?string,redirectTo:?string,redirectStatus:?int,view:array<string,mixed>}
     */
    public function handle(?string $slug, int $id, string $method, array $post, array $server): array
    {
        if (!$slug && $id < 1) {
            return $this->abort(
                404,
                'Lien de cours invalide',
                "Le lien ouvert est incomplet ou incorrect. Verifiez l'adresse, puis essayez a nouveau depuis le catalogue."
            );
        }

        $currentUser = $this->currentUser();
        $course = $this->model->findApprovedCourse($slug, $id);
        if (!$course) {
            $restrictedCourse = $this->model->findCourseBySlugOrId($slug, $id);
            $previewRedirect = $this->previewRedirectForRestrictedCourse($restrictedCourse, $currentUser);
            if ($previewRedirect !== null) {
                return [
                    'httpStatus' => null,
                    'abortTitle' => null,
                    'abortMessage' => null,
                    'redirectTo' => $previewRedirect,
                    'redirectStatus' => 302,
                    'view' => [],
                ];
            }
            return $this->abort(
                404,
                'Cours indisponible',
                "Le cours que vous cherchez n'est plus accessible publiquement. Il a peut-etre ete retire, renomme, ou le lien n'est plus valide."
            );
        }

        $profileModel = new WebProfileModel($this->pdo());
        $teacherUser = $profileModel->findUserById((int) ($course['teacher_id'] ?? 0));
        $teacherProfile = $teacherUser ? $profileModel->findTeacherProfileByUserId((int) ($course['teacher_id'] ?? 0)) : [];

        $relatedCourses = [];
        $relatedCoursesLabel = '';
        $courseId = (int) ($course['id'] ?? 0);
        $isFavorite = false;
        if ($currentUser && $courseId > 0) {
            try {
                $favoritesModel = new ApiCatalogModel($this->pdo());
                if ($favoritesModel->courseFavoritesTableReady()) {
                    $isFavorite = $favoritesModel->isCourseFavorite((int) ($currentUser['id'] ?? 0), $courseId);
                }
            } catch (Throwable $e) {
                $isFavorite = false;
            }
        }
        $teacherId = (int) ($course['teacher_id'] ?? 0);
        $categoryId = (int) ($course['category_id'] ?? 0);
        if ($teacherId > 0) {
            $relatedCourses = $this->model->fetchApprovedCoursesByTeacherId($teacherId, $courseId, 3);
            if ($relatedCourses !== []) {
                $relatedCoursesLabel = 'Plus de cours de ce formateur';
            }
        }
        if ($relatedCourses === [] && $categoryId > 0) {
            $relatedCourses = $this->model->fetchApprovedCoursesByCategoryId($categoryId, $courseId, 3);
            if ($relatedCourses !== []) {
                $relatedCoursesLabel = 'Cours similaires dans la même catégorie';
            }
        }

        if (strtoupper($method) === 'GET') {
            $canonicalRedirect = $this->courseCanonicalRedirect($course, $slug, $id, $_GET);
            if ($canonicalRedirect !== null) {
                return [
                    'httpStatus' => null,
                    'abortTitle' => null,
                    'abortMessage' => null,
                    'redirectTo' => $canonicalRedirect,
                    'redirectStatus' => 301,
                    'view' => [],
                ];
            }
        }

        $seoCanonicalUrl = $this->absUrl($this->courseCanonicalPath($course));
        $seoDescription = $this->seoCourseDescription($course);
        $seoImageUrl = $this->resolveSeoImageUrl((string) ($course['cover_path'] ?? ''));

        $meetingsHasDuration = $this->schema()->ensureCourseMeetingsDurationColumn();
        $courseViewsCount = 0;
        try {
            if (strtoupper($method) === 'GET') {
                $viewerKey = $this->buildCourseViewerKey($currentUser);
                if ($viewerKey !== '') {
                    $this->model->registerUniqueCourseView(
                        (int) $course['id'],
                        $currentUser ? (int) ($currentUser['id'] ?? 0) : null,
                        $viewerKey
                    );
                }
            }
            $courseViewsCount = $this->model->countCourseViews((int) $course['id']);
        } catch (Throwable $e) {
            // Keep course page available when migration/course_views table is not yet applied.
            $courseViewsCount = 0;
        }

        $chapters = $this->model->fetchChapters((int) $course['id']);
        $chapterMap = [];
        foreach ($chapters as $chapter) {
            $chapterMap[(int) $chapter['id']] = [
                'title' => (string) ($chapter['title'] ?? ''),
                'pos' => (string) ($chapter['position'] ?? ''),
            ];
        }

        $lessonsByChapter = [];
        $docsByChapter = [];
        $docsByLesson = [];
        $meetingsByChapter = [];
        $chapterLessonCount = [];
        $chapterDuration = [];
        $examSessions = [];

        if ($chapters !== []) {
            $chapterIds = array_map(static fn(array $row): int => (int) $row['id'], $chapters);

            $lessons = $this->model->fetchLessonsByChapterIds($chapterIds);
            foreach ($lessons as $lesson) {
                $chapterId = (int) ($lesson['chapter_id'] ?? 0);
                $lessonsByChapter[$chapterId][] = $lesson;
                $chapterLessonCount[$chapterId] = (int) ($chapterLessonCount[$chapterId] ?? 0) + 1;
                if ($lesson['duration_estimate_min'] !== null) {
                    $chapterDuration[$chapterId] = (int) ($chapterDuration[$chapterId] ?? 0) + (int) $lesson['duration_estimate_min'];
                }
            }

            $documents = $this->model->fetchDocumentsByChapterIds($chapterIds);
            foreach ($documents as $document) {
                $document['label'] = $this->documentLabel(
                    isset($document['type']) ? (string) $document['type'] : null,
                    isset($document['mime']) ? (string) $document['mime'] : null
                );
                $chapterId = (int) ($document['chapter_id'] ?? 0);
                $lessonId = $this->docLessonIdFromMime(is_string($document['mime'] ?? null) ? $document['mime'] : null);
                if ($lessonId > 0) {
                    $docsByLesson[$lessonId][] = $document;
                } else {
                    $docsByChapter[$chapterId][] = $document;
                }
            }

            $meetingTemplates = $this->model->fetchMeetingTemplatesByChapterIds($chapterIds, $meetingsHasDuration);
            foreach ($meetingTemplates as $meetingTemplate) {
                $meetingsByChapter[(int) ($meetingTemplate['chapter_id'] ?? 0)][] = $meetingTemplate;
            }

            $examSessions = $this->model->fetchExamSessions((int) $course['id']);
        }

        $totalMin = array_sum($chapterDuration);
        $durationLabel = $this->formatDurationLabel($totalMin, (float) ($course['duration_hours'] ?? 0));

        $promoData = $this->promotions->coursePromoData(
            $course,
            null,
            !empty($currentUser['id']) ? (int) $currentUser['id'] : null
        );
        $priceFinal = (int) ($promoData['price_xof'] ?? ($course['price_xof'] ?? 0));
        $priceBase = (int) ($promoData['base_price_xof'] ?? ($course['price_xof'] ?? 0));
        $promoActive = !empty($promoData['active']) && $priceBase > $priceFinal;
        $promoEndLabel = '';
        $promoDiscountLabel = '';
        if (!empty($promoData['end_at'])) {
            $promoEndTs = strtotime((string) $promoData['end_at']);
            if ($promoEndTs) {
                $promoEndLabel = date('Y-m-d H:i', $promoEndTs);
            }
        }
        if ($promoActive) {
            $promoType = (string) ($promoData['promo_type'] ?? '');
            $promoValue = (float) ($promoData['promo_value'] ?? 0);
            if ($promoType === 'percent' && $promoValue > 0) {
                $promoDiscountLabel = '-' . rtrim(rtrim(number_format($promoValue, 2, '.', ''), '0'), '.') . '%';
            } elseif ($promoType === 'amount') {
                $discountAmount = max(0, $priceBase - $priceFinal);
                $promoDiscountLabel = '-' . number_format($discountAmount, 0, ',', ' ') . ' F CFA';
            }
        }

        $ratingStatsRaw = $this->model->fetchCourseRatingStats((int) $course['id']);
        $ratingStats = [
            'avg' => !empty($ratingStatsRaw['avg_rating']) ? round((float) $ratingStatsRaw['avg_rating'], 1) : null,
            'count' => (int) ($ratingStatsRaw['cnt'] ?? 0),
        ];
        $reviews = $this->model->fetchCourseReviews((int) $course['id'], 5);

        $isEnrolled = false;
        if ($currentUser) {
            $isEnrolled = $this->model->hasActiveEnrollment((int) $currentUser['id'], (int) $course['id']);
        }
        $doneByLesson = [];
        $progressPercent = 0;
        $totalLessons = 0;
        $firstVideoId = null;
        $firstEmbedUrl = null;
        $firstEmbedTitle = '';
        $publicPreviewAvailable = false;
        $publicPreviewLessonId = null;
        $publicPreviewEmbedUrl = null;
        $publicPreviewTitle = '';
        $publicPreviewOpenUrl = '';
        $videoPlaylist = [];
        $meetingErrors = [];
        $meetingSuccess = (string) $this->flashGet('meeting_success', '');
        $lessonsFlat = [];
        $firstLessonId = null;

        foreach ($chapters as $chapter) {
            $chapterId = (int) ($chapter['id'] ?? 0);
            if ($chapterId < 1) {
                continue;
            }
            foreach (($lessonsByChapter[$chapterId] ?? []) as $lesson) {
                $lessonsFlat[] = $lesson;

                if ($firstLessonId === null) {
                    $lessonIdCandidate = (int) ($lesson['id'] ?? 0);
                    if ($lessonIdCandidate > 0) {
                        $firstLessonId = $lessonIdCandidate;
                    }
                }

                if (($lesson['lesson_type'] ?? '') !== 'video' || empty($lesson['youtube_url'])) {
                    continue;
                }

                $youtubeUrl = (string) $lesson['youtube_url'];
                $embedUrl = $this->youtubeEmbedUrl($youtubeUrl);
                if ($embedUrl === null) {
                    continue;
                }

                if (!$publicPreviewAvailable) {
                    $publicPreviewAvailable = true;
                    $publicPreviewLessonId = (int) ($lesson['id'] ?? 0);
                    $publicPreviewTitle = (string) ($lesson['title'] ?? '');
                    $publicPreviewEmbedUrl = $this->youtubePublicPreviewEmbedUrl($youtubeUrl);
                }

                if ($isEnrolled) {
                    $videoId = $this->youtubeVideoId($youtubeUrl);
                    if ($videoId) {
                        $chapterMeta = $chapterMap[(int) ($lesson['chapter_id'] ?? 0)] ?? ['title' => '', 'pos' => ''];
                        $videoPlaylist[] = [
                            'id' => (int) ($lesson['id'] ?? 0),
                            'title' => (string) ($lesson['title'] ?? ''),
                            'video_id' => $videoId,
                            'embed' => $embedUrl,
                            'done' => false,
                            'duration_seconds' => max(0, ((int) ($lesson['duration_estimate_min'] ?? 0)) * 60),
                            'chapter_id' => (int) ($lesson['chapter_id'] ?? 0),
                            'chapter_title' => (string) ($chapterMeta['title'] ?? ''),
                            'chapter_pos' => (string) ($chapterMeta['pos'] ?? ''),
                            'lesson_pos' => (string) ($lesson['position'] ?? ''),
                        ];
                    }
                }
            }
        }

        if ($currentUser && $isEnrolled) {
            $totalLessons = count($lessonsFlat);
            $doneByLesson = array_values(array_unique(
                $this->model->fetchDoneLessonIds((int) $currentUser['id'], (int) $course['id'])
            ));
            $validLessonIds = [];
            foreach ($lessonsFlat as $lessonRow) {
                if (is_array($lessonRow) && (int) ($lessonRow['id'] ?? 0) > 0) {
                    $validLessonIds[] = (int) $lessonRow['id'];
                }
            }
            if ($validLessonIds !== []) {
                $validLessonLookup = array_flip(array_unique($validLessonIds));
                $doneByLesson = array_values(array_filter(
                    $doneByLesson,
                    static fn (int $lessonId): bool => isset($validLessonLookup[$lessonId])
                ));
            } else {
                $doneByLesson = [];
            }
            foreach ($videoPlaylist as $index => $playlistItem) {
                $videoPlaylist[$index]['done'] = in_array((int) $playlistItem['id'], $doneByLesson, true);
            }
            $progressPercent = $totalLessons > 0
                ? (int) floor((count($doneByLesson) / $totalLessons) * 100)
                : 0;
            $progressPercent = max(0, min(100, $progressPercent));

            if ($videoPlaylist !== []) {
                $firstPlaylistItem = $videoPlaylist[0];
                $firstVideoId = (int) ($firstPlaylistItem['id'] ?? 0);
                $firstEmbedTitle = (string) ($firstPlaylistItem['title'] ?? '');
                $firstEmbedUrl = (string) ($firstPlaylistItem['embed'] ?? '');
            }
        }
        if ($publicPreviewAvailable && !$isEnrolled && (int) $publicPreviewLessonId > 0) {
            $viewerKey = $this->buildCourseViewerKey($currentUser);
            if ($viewerKey !== '') {
                $previewToken = $this->previewTokens->issueCoursePreviewToken(
                    (int) $course['id'],
                    (int) $publicPreviewLessonId,
                    $viewerKey,
                    180
                );
                if ($previewToken !== '') {
                    $publicPreviewOpenUrl = $this->siteUrl(
                        'course_preview_open?course_id=' . (int) $course['id'] . '&token=' . rawurlencode($previewToken)
                    );
                }
            }
        }

        $doneCount = min($totalLessons, count($doneByLesson));
        $totalLessonsAll = array_sum($chapterLessonCount);
        $resourceCount = 0;
        foreach ($docsByChapter as $chapterDocs) {
            if (is_array($chapterDocs)) {
                $resourceCount += count($chapterDocs);
            }
        }
        foreach ($docsByLesson as $lessonDocs) {
            if (is_array($lessonDocs)) {
                $resourceCount += count($lessonDocs);
            }
        }
        $objectivesList = $this->courseFieldAsList($course['objectives'] ?? null);
        $prerequisitesList = $this->courseFieldAsList($course['prerequisites'] ?? null);
        $equipmentList = $this->courseFieldAsList($course['equipment'] ?? null);
        $doneByChapter = [];
        if ($isEnrolled && $doneByLesson !== []) {
            $doneSet = array_fill_keys($doneByLesson, true);
            foreach ($lessonsByChapter as $chapterId => $lessonList) {
                $count = 0;
                foreach ($lessonList as $lesson) {
                    $lessonId = (int) ($lesson['id'] ?? 0);
                    if ($lessonId > 0 && isset($doneSet[$lessonId])) {
                        $count++;
                    }
                }
                $doneByChapter[(int) $chapterId] = $count;
            }
        }
        $examLocked = $isEnrolled && $totalLessons > 0 && $doneCount < $totalLessons;

        if (strtoupper($method) === 'POST') {
            $action = (string) ($post['action'] ?? '');

            if ($action === 'request_meeting') {
                $meetingResult = $this->handleMeetingRequest(
                    $course,
                    $currentUser,
                    $isEnrolled,
                    $meetingsHasDuration,
                    $post
                );
                if (!empty($meetingResult['redirectTo'])) {
                    return [
                        'httpStatus' => null,
                        'abortTitle' => null,
                        'abortMessage' => null,
                        'redirectTo' => (string) $meetingResult['redirectTo'],
                        'redirectStatus' => null,
                        'view' => [],
                    ];
                }
                $meetingErrors = $meetingResult['errors'];
            }

            if ($action === 'cancel_meeting_requests') {
                $cancelResult = $this->handleMeetingCancel($course, $currentUser, $isEnrolled, $post);
                if (!empty($cancelResult['redirectTo'])) {
                    return [
                        'httpStatus' => null,
                        'abortTitle' => null,
                        'abortMessage' => null,
                        'redirectTo' => (string) $cancelResult['redirectTo'],
                        'redirectStatus' => null,
                        'view' => [],
                    ];
                }
                $meetingErrors = $cancelResult['errors'];
            }

        }

        $myMeetingsByKey = [];
        if ($currentUser && $isEnrolled) {
            $myMeetings = $this->model->fetchStudentMeetings((int) $course['id'], (int) $currentUser['id']);
            foreach ($myMeetings as $row) {
                $row['start_local'] = $this->dateTimeLocalValue(isset($row['start_at']) ? (string) $row['start_at'] : null);
                $key = (int) ($row['chapter_id'] ?? 0) . '|' . (string) ($row['title'] ?? '') . '|' . (string) ($row['mode'] ?? '');
                $myMeetingsByKey[$key][] = $row;
            }
        }

        $checkoutPath = $this->siteUrl('checkout?course=' . (int) $course['id']);
        $ctaHref = $currentUser ? $checkoutPath : $this->siteUrl('login?return=' . urlencode($checkoutPath));
        $levelLabels = [
            'beginner' => 'Débutant',
            'intermediate' => 'Intermédiaire',
            'advanced' => 'Avancé',
        ];

        $avgRatingRaw = $this->model->fetchCourseRatingStats((int) $course['id']);
        $avgRating = !empty($avgRatingRaw['avg_rating']) ? round((float) $avgRatingRaw['avg_rating'], 2) : 0;
        $ratingCount = (int) ($avgRatingRaw['cnt'] ?? 0);
        $userRating = null;
        if ($currentUser) {
            $userRating = $this->model->fetchUserCourseRating((int) $course['id'], (int) $currentUser['id']);
        }
        $buyersCount = $this->model->countActiveBuyers((int) $course['id']);

        $platformReferral = $this->referrals->getPlatformReferralSettings();
        $referralEnabled = (int) ($course['referral_override'] ?? 0) === 1;
        $referralL1Label = number_format((float) ($platformReferral['l1_rate'] ?? 0), 2, '.', '');
        $referralL2Label = number_format((float) ($platformReferral['l2_rate'] ?? 0), 2, '.', '');
        $referralL2Enabled = $referralEnabled
            && (int) ($course['referral_l2_enabled'] ?? 0) === 1
            && (float) ($platformReferral['l2_rate'] ?? 0) > 0;

        $shareToken = $this->promotions->ensureCourseShareToken((int) $course['id']);
        $courseShareUrl = $shareToken
            ? $this->absUrl('share/' . $shareToken . '/')
            : (!empty($course['slug'])
                ? $this->absUrl('course?slug=' . urlencode((string) $course['slug']))
                : $this->absUrl('course?id=' . (int) $course['id']));
        $shareText = 'Je te recommande ce cours : ' . ((string) ($course['title'] ?? 'Cours'));
        $shareUrlEnc = rawurlencode($courseShareUrl);
        $shareTextEnc = rawurlencode($shareText);
        $shareTextUrlEnc = rawurlencode($shareText . ' ' . $courseShareUrl);
        $shareLinks = [
            'whatsapp' => 'https://wa.me/?text=' . $shareTextUrlEnc,
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $shareUrlEnc,
            'x' => 'https://twitter.com/intent/tweet?url=' . $shareUrlEnc . '&text=' . $shareTextEnc,
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $shareUrlEnc,
        ];
        $referralCode = '';
        $referralLink = '';
        $canShareReferral = $currentUser && $this->referrals->userHasActiveEnrollment((int) $currentUser['id']);
        if ($canShareReferral) {
            $userReferralCode = $this->referrals->ensureUserReferralCode((int) $currentUser['id']);
            if ($userReferralCode) {
                $referralCode = $userReferralCode;
                $referralLink = $this->absUrl('register?ref=' . urlencode($userReferralCode));
            }
        }

        return [
            'httpStatus' => null,
            'abortTitle' => null,
            'abortMessage' => null,
            'redirectTo' => null,
            'redirectStatus' => null,
            'view' => [
                'siteLogoUrl' => $this->siteLogoUrl(),
                'csp' => $this->buildCsp(),
                'course' => $course,
                'seoCanonicalUrl' => $seoCanonicalUrl,
                'seoDescription' => $seoDescription,
                'seoImageUrl' => $seoImageUrl,
                'chapters' => $chapters,
                'lessonsByChapter' => $lessonsByChapter,
                'docsByChapter' => $docsByChapter,
                'docsByLesson' => $docsByLesson,
                'meetingsByChapter' => $meetingsByChapter,
                'chapterLessonCount' => $chapterLessonCount,
                'chapterDuration' => $chapterDuration,
                'examSessions' => $examSessions,
                'teacherUser' => $teacherUser,
                'teacherProfile' => $teacherProfile,
                'relatedCourses' => $relatedCourses,
                'relatedCoursesLabel' => $relatedCoursesLabel,
                'resourceCount' => $resourceCount,
                'durationLabel' => $durationLabel,
                'objectivesList' => $objectivesList,
                'prerequisitesList' => $prerequisitesList,
                'equipmentList' => $equipmentList,
                'currentUser' => $currentUser,
                'isEnrolled' => $isEnrolled,
                'doneByLesson' => $doneByLesson,
                'progressPercent' => $progressPercent,
                'totalLessons' => $totalLessons,
                'firstLessonId' => $firstLessonId,
                'firstVideoId' => $firstVideoId,
                'firstEmbedUrl' => $firstEmbedUrl,
                'firstEmbedTitle' => $firstEmbedTitle,
                'publicPreviewAvailable' => $publicPreviewAvailable,
                'publicPreviewLessonId' => $publicPreviewLessonId,
                'publicPreviewEmbedUrl' => $publicPreviewEmbedUrl,
                'publicPreviewTitle' => $publicPreviewTitle,
                'publicPreviewOpenUrl' => $publicPreviewOpenUrl,
                'videoPlaylist' => $videoPlaylist,
                'meetingErrors' => $meetingErrors,
                'meetingSuccess' => $meetingSuccess,
                'doneCount' => $doneCount,
                'totalLessonsAll' => $totalLessonsAll,
                'doneByChapter' => $doneByChapter,
                'examLocked' => $examLocked,
                'myMeetingsByKey' => $myMeetingsByKey,
                'ctaHref' => $ctaHref,
                'levelLabels' => $levelLabels,
                'avgRating' => $avgRating,
                'ratingCount' => $ratingCount,
                'courseViewsCount' => $courseViewsCount,
                'userRating' => $userRating,
                'buyersCount' => $buyersCount,
                'ratingStats' => $ratingStats,
                'reviews' => $reviews,
                'priceFinal' => $priceFinal,
                'priceBase' => $priceBase,
                'promoActive' => $promoActive,
                'promoEndLabel' => $promoEndLabel,
                'promoDiscountLabel' => $promoDiscountLabel,
                'referralEnabled' => $referralEnabled,
                'referralL1Label' => $referralL1Label,
                'referralL2Label' => $referralL2Label,
                'referralL2Enabled' => $referralL2Enabled,
                'isFavorite' => $isFavorite,
                'courseShareUrl' => $courseShareUrl,
                'shareLinks' => $shareLinks,
                'referralCode' => $referralCode,
                'referralLink' => $referralLink,
            ],
        ];
    }

    private function buildCourseViewerKey(?array $currentUser): string
    {
        $userId = (int) ($currentUser['id'] ?? 0);
        if ($userId > 0) {
            return 'u:' . $userId;
        }

        $token = trim((string) ($_SESSION['course_viewer_token'] ?? ''));
        if ($token === '' || strlen($token) < 16) {
            try {
                $token = bin2hex(random_bytes(24));
            } catch (Throwable $e) {
                $token = session_id() !== '' ? session_id() : uniqid('course_viewer_', true);
            }
            $_SESSION['course_viewer_token'] = $token;
        }

        return 'g:' . hash('sha256', $token);
    }

    /**
     * @param array<string,mixed>|null $course
     * @param array<string,mixed>|null $currentUser
     */
    private function previewRedirectForRestrictedCourse(?array $course, ?array $currentUser): ?string
    {
        if (!is_array($course) || !is_array($currentUser)) {
            return null;
        }

        $courseId = (int) ($course['id'] ?? 0);
        $userId = (int) ($currentUser['id'] ?? 0);
        if ($courseId < 1 || $userId < 1) {
            return null;
        }

        $roles = $this->rolesFromJson((string) ($currentUser['roles_json'] ?? '[]'));
        $isOwnerTeacher = in_array('teacher', $roles, true)
            && (int) ($course['teacher_id'] ?? 0) === $userId;
        if ($isOwnerTeacher) {
            return $this->siteUrl('teacher/course_preview?id=' . $courseId);
        }

        $isAdmin = in_array('admin', $roles, true) || in_array('admin_secondary', $roles, true);
        if ($isAdmin) {
            return $this->siteUrl('admin/course_preview?id=' . $courseId);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function rolesFromJson(string $rolesJson): array
    {
        $decoded = json_decode($rolesJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        $roles = [];
        foreach ($decoded as $role) {
            $role = trim((string) $role);
            if ($role !== '') {
                $roles[] = $role;
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * @return array{errors:array<int,string>,redirectTo:?string}
     */
    private function handleMeetingRequest(array $course, ?array $currentUser, bool $isEnrolled, bool $meetingsHasDuration, array $post): array
    {
        $errors = [];
        if (!$currentUser) {
            return ['errors' => [], 'redirectTo' => $this->siteUrl('login?return=' . urlencode($_SERVER['REQUEST_URI'] ?? ''))];
        }
        if (!$isEnrolled) {
            $errors[] = "Vous devez \u{00ea}tre inscrit au cours pour demander un rendez-vous.";
        }

        $templateId = (int) ($post['meeting_template_id'] ?? 0);
        if ($templateId < 1) {
            $errors[] = 'Rendez-vous invalide.';
        }

        $template = null;
        if ($errors === []) {
            $template = $this->model->findMeetingTemplateForRequest($templateId, (int) $course['id'], $meetingsHasDuration);
            if (!$template) {
                $errors[] = 'Rendez-vous introuvable.';
            }
        }

        $starts = $post['slot_start'] ?? [];
        $durationMin = 0;
        if ($errors === [] && (!is_array($starts) || count($starts) < 3)) {
            $errors[] = "Merci de proposer 3 cr\u{00e9}neaux.";
        }

        $slots = [];
        if ($errors === [] && $template) {
            $now = time();
            $durationMin = (int) ($template['duration_min'] ?? 0);
            if ($durationMin < 1) {
                $errors[] = "La dur\u{00e9}e du rendez-vous n'est pas d\u{00e9}finie. Le formateur doit la renseigner avant que vous proposiez des cr\u{00e9}neaux.";
            } else {
                for ($i = 0; $i < 3; $i++) {
                    $startAt = $this->parseDtLocal((string) ($starts[$i] ?? ''));
                    if (!$startAt) {
                        $errors[] = "Cr\u{00e9}neau " . ($i + 1) . ' invalide.';
                        break;
                    }
                    if (strtotime($startAt) < $now) {
                        $errors[] = "Le cr\u{00e9}neau " . ($i + 1) . " doit \u{00ea}tre dans le futur.";
                        break;
                    }
                    $endTs = strtotime($startAt) + ($durationMin * 60);
                    $endAt = date('Y-m-d H:i:s', $endTs);
                    $slots[] = [$startAt, $endAt];
                }
            }
        }

        if ($errors === [] && $template) {
            $hasConfirmed = $this->model->hasConfirmedMeeting(
                (int) $course['id'],
                $template['chapter_id'] ?? null,
                (string) ($template['title'] ?? ''),
                (string) ($template['mode'] ?? ''),
                (int) $currentUser['id']
            );
            if ($hasConfirmed) {
                $errors[] = "Votre rendez-vous est d\u{00e9}j\u{00e0} confirm\u{00e9}. Contactez le formateur pour replanifier.";
            }
        }

        if ($errors !== [] || !$template) {
            return ['errors' => $errors, 'redirectTo' => null];
        }

        try {
            $this->model->beginTransaction();
            $this->model->cancelPendingMeetingRequests(
                (int) $course['id'],
                $template['chapter_id'] ?? null,
                (string) ($template['title'] ?? ''),
                (string) ($template['mode'] ?? ''),
                (int) $currentUser['id']
            );

            foreach ($slots as [$startAt, $endAt]) {
                if ($meetingsHasDuration) {
                    $this->model->insertPendingMeetingSlotWithDuration(
                        (int) $course['id'],
                        $template['chapter_id'] ?? null,
                        (string) ($template['title'] ?? ''),
                        (string) ($template['mode'] ?? ''),
                        $durationMin,
                        (int) $currentUser['id'],
                        $startAt,
                        $endAt
                    );
                } else {
                    $this->model->insertPendingMeetingSlot(
                        (int) $course['id'],
                        $template['chapter_id'] ?? null,
                        (string) ($template['title'] ?? ''),
                        (string) ($template['mode'] ?? ''),
                        (int) $currentUser['id'],
                        $startAt,
                        $endAt
                    );
                }
            }

            $teacherId = (int) ($course['teacher_id'] ?? 0);
            if ($teacherId > 0) {
                $slotsText = implode(' | ', array_map(static fn(array $slot): string => $slot[0] . ' - ' . $slot[1], $slots));
                $this->model->insertNotification(
                    $teacherId,
                    'Nouvelle demande de rendez-vous',
                    trim(((string) ($currentUser['first_name'] ?? "Un \u{00e9}tudiant")) . ' ' . ((string) ($currentUser['last_name'] ?? ''))) . " a propos\u{00e9} des cr\u{00e9}neaux pour \"" . ((string) ($course['title'] ?? 'Cours')) . "\" : " . $slotsText,
                    $this->absUrl('teacher/meetings?status=pending&course_id=' . (int) $course['id'])
                );
            }

            $this->model->commit();
            $this->flashSet('meeting_success', "Demande envoy\u{00e9}e. Le formateur confirmera un cr\u{00e9}neau.");
            return ['errors' => [], 'redirectTo' => $this->selfRedirect('course')];
        } catch (Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            return ['errors' => ["Erreur lors de l'envoi de la demande."], 'redirectTo' => null];
        }
    }

    /**
     * @return array{errors:array<int,string>,redirectTo:?string}
     */
    private function handleMeetingCancel(array $course, ?array $currentUser, bool $isEnrolled, array $post): array
    {
        $errors = [];
        if (!$currentUser) {
            return ['errors' => [], 'redirectTo' => $this->siteUrl('login?return=' . urlencode($_SERVER['REQUEST_URI'] ?? ''))];
        }
        if (!$isEnrolled) {
            $errors[] = "Vous devez \u{00ea}tre inscrit au cours.";
        }

        $templateId = (int) ($post['meeting_template_id'] ?? 0);
        if ($templateId < 1) {
            $errors[] = 'Rendez-vous invalide.';
        }

        $template = null;
        if ($errors === []) {
            $template = $this->model->findMeetingTemplateForCancel($templateId, (int) $course['id']);
            if (!$template) {
                $errors[] = 'Rendez-vous introuvable.';
            }
        }

        if ($errors !== [] || !$template) {
            return ['errors' => $errors, 'redirectTo' => null];
        }

        try {
            $this->model->cancelPendingMeetingRequests(
                (int) $course['id'],
                $template['chapter_id'] ?? null,
                (string) ($template['title'] ?? ''),
                (string) ($template['mode'] ?? ''),
                (int) $currentUser['id']
            );
            $this->flashSet('meeting_success', "Demandes annul\u{00e9}es.");
            return ['errors' => [], 'redirectTo' => $this->selfRedirect('course')];
        } catch (Throwable $e) {
            return ['errors' => ["Erreur lors de l'annulation."], 'redirectTo' => null];
        }
    }

    private function youtubeVideoId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return null;
        }
        $host = (string) $parts['host'];
        if (str_contains($host, 'youtu.be')) {
            $videoId = trim((string) ($parts['path'] ?? ''), '/');
            return $videoId !== '' ? $videoId : null;
        }
        if (str_contains($host, 'youtube.com') && !empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            if (!empty($query['v']) && is_string($query['v'])) {
                return $query['v'];
            }
        }
        return null;
    }

    private function youtubeEmbedUrl(?string $url): ?string
    {
        $videoId = $this->youtubeVideoId($url);
        if (!$videoId) {
            return null;
        }
        $safeVideoId = htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8');
        $origin = $this->youtubeOrigin();
        $query = [
            'enablejsapi' => '1',
            'controls' => '0',
            'modestbranding' => '1',
            'rel' => '0',
            'playsinline' => '1',
            'iv_load_policy' => '3',
            'disablekb' => '1',
            'fs' => '0',
        ];
        if ($origin !== '') {
            $query['origin'] = $origin;
        }

        return 'https://www.youtube-nocookie.com/embed/' . $safeVideoId . '?' . http_build_query($query);
    }

    private function youtubePublicPreviewEmbedUrl(?string $url): ?string
    {
        $videoId = $this->youtubeVideoId($url);
        if (!$videoId) {
            return null;
        }
        $safeVideoId = htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8');
        $origin = $this->youtubeOrigin();
        $query = [
            'enablejsapi' => '1',
            'controls' => '1',
            'modestbranding' => '1',
            'rel' => '0',
            'playsinline' => '1',
            'iv_load_policy' => '3',
            'disablekb' => '0',
            'fs' => '1',
        ];
        if ($origin !== '') {
            $query['origin'] = $origin;
        }

        return 'https://www.youtube-nocookie.com/embed/' . $safeVideoId . '?' . http_build_query($query);
    }

    private function youtubeOrigin(): string
    {
        $url = trim($this->absUrl(''));
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = (string) ($parts['scheme'] ?? '');
        $host = (string) ($parts['host'] ?? '');
        if ($scheme === '' || $host === '') {
            return '';
        }

        $origin = $scheme . '://' . $host;
        if (isset($parts['port'])) {
            $origin .= ':' . (int) $parts['port'];
        }

        return $origin;
    }

    private function docLessonIdFromMime(?string $mime): int
    {
        if (!$mime) {
            return 0;
        }
        if (preg_match('/\|lesson:(\d+)/', $mime, $matches) === 1) {
            return (int) ($matches[1] ?? 0);
        }
        return 0;
    }

    /**
     * @return list<string>
     */
    private function courseFieldAsList(mixed $value): array
    {
        if ($value === null) {
            return [];
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        $items = is_array($decoded) ? $decoded : preg_split('/\r\n|\r|\n/', $raw);
        if (!is_array($items)) {
            return [];
        }
        $items = array_map(static fn($item): string => trim((string) $item), $items);
        return array_values(array_filter($items, static fn(string $item): bool => $item !== ''));
    }

    private function documentLabel(?string $type, ?string $mime): string
    {
        if ($type === 'exercise') {
            return 'Exercice';
        }
        if ($type === 'exam') {
            return 'Examen';
        }
        if ($type === 'resource') {
            if (is_string($mime) && stripos($mime, 'url_document') !== false) {
                return "Document d'accompagnement";
            }
            if (is_string($mime) && stripos($mime, 'url_file') !== false) {
                return 'Fichier';
            }
            return 'Ressource';
        }
        return 'Ressource';
    }

    private function dateTimeLocalValue(?string $dbValue): string
    {
        if (!$dbValue) {
            return '';
        }
        $ts = strtotime($dbValue);
        if (!$ts) {
            return '';
        }
        return date('Y-m-d\TH:i', $ts);
    }

    private function parseDtLocal(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $value);
        if ($dateTime instanceof DateTime) {
            return $dateTime->format('Y-m-d H:i:s');
        }
        $ts = strtotime($value);
        if (!$ts) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }

    private function formatDurationLabel(int $totalMin, float $courseDurationHours): ?string
    {
        if ($totalMin > 0) {
            return \format_duration_minutes_hm($totalMin);
        }

        if ($courseDurationHours <= 0) {
            return null;
        }

        $minutes = (int) round($courseDurationHours * 60);
        if ($minutes <= 0) {
            return null;
        }
        return \format_duration_minutes_hm($minutes);
    }

    private function buildCsp(): string
    {
        return "default-src 'self'; "
            . "frame-src https://www.youtube.com https://www.youtube-nocookie.com https://docs.google.com; "
            . "child-src https://www.youtube.com https://www.youtube-nocookie.com https://docs.google.com; "
            . "connect-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://s.ytimg.com https://i.ytimg.com https://*.googlevideo.com https://*.ggpht.com; "
            . "media-src 'self' https://www.youtube.com https://www.youtube-nocookie.com; "
            . "img-src 'self' data: blob: https://i.ytimg.com; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.youtube.com https://s.ytimg.com https://cdn.tailwindcss.com; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
            . "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com";
    }

    private function selfRedirect(string $fallback): string
    {
        return $this->sanitizeReturnPath($_SERVER['REQUEST_URI'] ?? null, $fallback);
    }

    /**
     * @return array{httpStatus:int,abortTitle:string,abortMessage:string,redirectTo:null,redirectStatus:null,view:array<string,mixed>}
     */
    private function abort(int $status, string $title, string $message): array
    {
        return [
            'httpStatus' => $status,
            'abortTitle' => $title,
            'abortMessage' => $message,
            'redirectTo' => null,
            'redirectStatus' => null,
            'view' => [],
        ];
    }

    /**
     * @param array<string,mixed> $course
     */
    private function courseCanonicalPath(array $course): string
    {
        $slug = trim((string) ($course['slug'] ?? ''));
        if ($slug !== '') {
            return 'course?slug=' . rawurlencode($slug);
        }
        return 'course?id=' . (int) ($course['id'] ?? 0);
    }

    /**
     * @param array<string,mixed> $course
     * @param array<string,mixed> $query
     */
    private function courseCanonicalRedirect(array $course, ?string $requestedSlug, int $requestedId, array $query): ?string
    {
        $canonicalSlug = trim((string) ($course['slug'] ?? ''));
        if ($canonicalSlug === '') {
            return null;
        }

        $currentSlug = trim((string) ($requestedSlug ?? ''));
        if ($currentSlug !== '' && strcasecmp($currentSlug, $canonicalSlug) === 0 && $requestedId < 1) {
            return null;
        }

        $params = ['slug' => $canonicalSlug];
        foreach ($query as $key => $value) {
            if (!is_string($key) || $key === 'slug' || $key === 'id' || is_array($value)) {
                continue;
            }
            $params[$key] = (string) $value;
        }

        return $this->siteUrl('course?' . http_build_query($params));
    }

    /**
     * @param array<string,mixed> $course
     */
    private function seoCourseDescription(array $course): string
    {
        $description = trim((string) ($course['description'] ?? ''));
        $description = preg_replace('/\s+/u', ' ', strip_tags($description)) ?? $description;
        if ($description === '') {
            $description = "Suivez ce cours v\u{00e9}rifi\u{00e9} sur \u{00c9}cole Num\u{00e9}rique et progressez avec des contenus pratiques.";
        }

        if (\function_exists('mb_strlen') && \function_exists('mb_substr')) {
            if (mb_strlen($description) <= 160) {
                return $description;
            }
            return rtrim(mb_substr($description, 0, 157)) . '...';
        }

        if (strlen($description) <= 160) {
            return $description;
        }
        return rtrim(substr($description, 0, 157)) . '...';
    }

    private function resolveSeoImageUrl(string $coverPath): string
    {
        $path = trim($coverPath);
        if ($path === '') {
            $logoUrl = trim($this->siteLogoUrl());
            if ($logoUrl !== '') {
                return $logoUrl;
            }
            return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 640 360%22%3E%3Crect width=%22640%22 height=%22360%22 rx=%2232%22 fill=%22%23e2e8f0%22/%3E%3Cpath d=%22M160 258h320%22 stroke=%22%2394a3b8%22 stroke-width=%2220%22 stroke-linecap=%22round%22/%3E%3Ccircle cx=%22246%22 cy=%22148%22 r=%2258%22 fill=%22%23cbd5e1%22/%3E%3Cpath d=%22M332 112h112c20 0 36 16 36 36v74c0 20-16 36-36 36H214c-20 0-36-16-36-36v-6%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%2218%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22/%3E%3C/svg%3E';
        }
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }
        return $this->absUrl(ltrim($path, '/'));
    }
}
