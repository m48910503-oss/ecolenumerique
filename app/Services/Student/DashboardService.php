<?php
declare(strict_types=1);

namespace App\Services\Student;

use App\Core\Service;
use App\Models\EnrollmentModel;
use App\Models\StudentDashboardModel;
use App\Services\Shared\CertificateOpsService;
use App\Services\Shared\ReferralService;

final class DashboardService extends Service
{
    private StudentDashboardModel $model;
    private EnrollmentModel $enrollments;
    private ReferralService $referrals;
    private CertificateOpsService $certificates;

    public function __construct(
        StudentDashboardModel $model,
        EnrollmentModel $enrollments,
        ReferralService $referrals,
        CertificateOpsService $certificates
    ) {
        $this->model = $model;
        $this->enrollments = $enrollments;
        $this->referrals = $referrals;
        $this->certificates = $certificates;
    }

    /**
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    public function buildViewData(array $user): array
    {
        $userId = (int) ($user['id'] ?? 0);

        $unreadCount = $this->model->countUnreadNotifications($userId);

        $supportUnreadMessages = 0;
        $supportUnreadNotifications = 0;
        try {
            $supportUnreadMessages = $this->model->countUnreadSupportReplies($userId);
        } catch (\Throwable $e) {
            $supportUnreadMessages = 0;
        }
        try {
            $supportUnreadNotifications = $this->model->countUnreadSupportNotifications($userId);
        } catch (\Throwable $e) {
            $supportUnreadNotifications = 0;
        }
        $supportUnreadCount = max($supportUnreadMessages, $supportUnreadNotifications);

        $notifications = $this->model->fetchNotifications($userId, 10);
        $activeEnrollments = $this->model->countActiveEnrollments($userId);

        $paidOrdersCount = 0;
        $completedCoursesCount = 0;
        $viewedCoursesCount = 0;
        $examAttemptsCount = 0;
        $popularCourses = [];
        $recentActivity = [];
        $nextMeeting = null;

        try {
            $paidOrdersCount = $this->model->countPaidOrders($userId);
        } catch (\Throwable $e) {
            $paidOrdersCount = 0;
        }
        try {
            $completedCoursesCount = $this->model->countCompletedCourses($userId);
        } catch (\Throwable $e) {
            $completedCoursesCount = 0;
        }
        try {
            $viewedCoursesCount = $this->model->countViewedCourses($userId);
        } catch (\Throwable $e) {
            $viewedCoursesCount = 0;
        }
        try {
            $examAttemptsCount = $this->model->countExamAttempts($userId);
        } catch (\Throwable $e) {
            $examAttemptsCount = 0;
        }
        try {
            $popularCourses = $this->model->fetchPopularCoursesForStudent($userId, 4);
        } catch (\Throwable $e) {
            $popularCourses = [];
        }
        try {
            $recentActivity = $this->model->fetchRecentActivity($userId, 8);
        } catch (\Throwable $e) {
            $recentActivity = [];
        }
        try {
            $nextMeeting = $this->model->fetchNextConfirmedMeeting($userId);
        } catch (\Throwable $e) {
            $nextMeeting = null;
        }

        $allEnrollments = [];
        try {
            $allEnrollments = $this->enrollments->findWithCourseProgressByUserId($userId);
        } catch (\Throwable $e) {
            $allEnrollments = [];
        }
        $recentEnrollments = array_slice($allEnrollments, 0, 4);

        $overallLessonsDone = 0;
        $overallLessonsTotal = 0;
        $activeEnrollmentsThisMonth = 0;
        $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');
        foreach ($allEnrollments as $row) {
            $totalLessons = max(0, (int) ($row['total_lessons'] ?? 0));
            $doneLessons = max(0, (int) ($row['done_lessons'] ?? 0));
            $overallLessonsTotal += $totalLessons;
            if ($totalLessons > 0) {
                $overallLessonsDone += min($doneLessons, $totalLessons);
            }

            $enrolledAt = trim((string) ($row['enrolled_at'] ?? ''));
            if ($enrolledAt === '') {
                continue;
            }

            try {
                $enrolledDate = new \DateTimeImmutable($enrolledAt);
            } catch (\Throwable $e) {
                continue;
            }

            if ($enrolledDate >= $monthStart) {
                $activeEnrollmentsThisMonth++;
            }
        }
        $overallProgressPct = $overallLessonsTotal > 0
            ? (int) round(($overallLessonsDone / max(1, $overallLessonsTotal)) * 100)
            : 0;

        $resolveCourseUrl = function (array $course): string {
            $slug = trim((string) ($course['slug'] ?? ''));
            if ($slug !== '') {
                return $this->siteUrl('course/watch?slug=' . urlencode($slug));
            }

            $courseId = max(0, (int) ($course['id'] ?? $course['course_id'] ?? 0));
            if ($courseId > 0) {
                return $this->siteUrl('course/watch?id=' . $courseId);
            }

            return $this->siteUrl('student/my_courses');
        };

        $resolveCourseCoverUrl = static function (array $course): string {
            $coverPath = trim((string) ($course['cover_path'] ?? ''));
            if ($coverPath === '') {
                return '';
            }

            if (preg_match('#^https?://#i', $coverPath)) {
                return $coverPath;
            }

            return site_url(ltrim($coverPath, '/'));
        };

        $upcomingMeetingsCount = 0;
        try {
            $upcomingMeetingsCount = $this->model->countUpcomingMeetings($userId);
        } catch (\Throwable $e) {
            $upcomingMeetingsCount = 0;
        }

        $examTodoCount = 0;
        $examPendingCount = 0;
        $examGradedCount = 0;
        $examItems = [];
        $examMeetingById = [];
        $attemptsHasMeetingId = $this->schema()->examAttemptsHasMeetingId();

        try {
            $rows = $this->model->fetchExamRows($userId, $attemptsHasMeetingId, 50);

            $meetingIds = [];
            foreach ($rows as $row) {
                if (($row['exam_type'] ?? '') === 'meeting' && !empty($row['last_meeting_id'])) {
                    $meetingIds[] = (int) $row['last_meeting_id'];
                }
            }
            $meetingIds = array_values(array_unique(array_filter($meetingIds, static fn ($x): bool => $x > 0)));

            if ($attemptsHasMeetingId && $meetingIds !== []) {
                foreach ($this->model->fetchExamMeetingsByIds($meetingIds) as $meeting) {
                    $examMeetingById[(int) ($meeting['id'] ?? 0)] = $meeting;
                }
            }

            foreach ($rows as $row) {
                $attemptsCount = (int) ($row['attempts_count'] ?? 0);
                $maxAttempts = max(1, (int) ($row['max_attempts'] ?? 1));
                $lastStatus = (string) ($row['last_status'] ?? '');
                $examType = (string) ($row['exam_type'] ?? '');
                $availableSlots = (int) ($row['available_slots'] ?? 0);

                $statusKey = 'todo';
                $priority = 1;

                if ($lastStatus === 'submitted') {
                    $statusKey = 'pending';
                    $priority = 0;
                    $examPendingCount++;
                } elseif ($lastStatus === 'graded') {
                    $statusKey = 'graded';
                    $priority = 3;
                    $examGradedCount++;
                } elseif ($attemptsCount >= $maxAttempts && $examType !== 'meeting') {
                    $statusKey = 'locked';
                    $priority = 4;
                } elseif ($examType === 'meeting' && $availableSlots < 1 && empty($row['last_meeting_id'])) {
                    $statusKey = 'no_slot';
                    $priority = 2;
                } else {
                    $statusKey = 'todo';
                    $priority = 1;
                    if ($examType !== 'meeting' || empty($row['last_meeting_id'])) {
                        $examTodoCount++;
                    }
                }

                $row['_status_key'] = $statusKey;
                $row['_priority'] = $priority;
                $examItems[] = $row;
            }

            usort($examItems, static function (array $a, array $b): int {
                $pa = (int) ($a['_priority'] ?? 99);
                $pb = (int) ($b['_priority'] ?? 99);
                if ($pa !== $pb) {
                    return $pa <=> $pb;
                }
                $ad = (string) ($a['last_submitted_at'] ?? $a['created_at'] ?? '');
                $bd = (string) ($b['last_submitted_at'] ?? $b['created_at'] ?? '');
                return strcmp($bd, $ad);
            });
        } catch (\Throwable $e) {
            $examTodoCount = 0;
            $examPendingCount = 0;
            $examGradedCount = 0;
            $examItems = [];
            $examMeetingById = [];
        }

        $certificates = [];
        $certCount = 0;
        if ($this->certificates->hasCertificatesTable()) {
            try {
                $certificates = $this->model->fetchIssuedCertificates($userId, 10);
                $certCount = count($certificates);
            } catch (\Throwable $e) {
                $certificates = [];
                $certCount = 0;
            }
        }

        $referralCode = null;
        $referralLink = '';
        $canShareReferral = $this->referrals->userHasActiveEnrollment($userId);
        $referralEarnings = 0;
        $earnedByLevel = ['1' => 0, '2' => 0];
        $referralByUser = [];
        try {
            if ($canShareReferral) {
                $referralCode = $this->referrals->ensureUserReferralCode($userId);
            }
            if ($referralCode) {
                $referralLink = $this->absUrl('register?ref=' . urlencode((string) $referralCode));
            }
            if ($this->referrals->ensureReferralCommissionsTable()) {
                $referralEarnings = $this->model->sumReferralEarnings($userId);

                foreach ($this->model->sumReferralEarningsByLevel($userId) as $row) {
                    $level = (int) ($row['level'] ?? 0);
                    $amount = (int) ($row['total_amount'] ?? 0);
                    if ($level === 1 || $level === 2) {
                        $earnedByLevel[(string) $level] = $amount;
                    }
                }

                $referralByUser = $this->model->fetchReferralByUser($userId, 50);
            }
        } catch (\Throwable $e) {
            $referralEarnings = 0;
            $earnedByLevel = ['1' => 0, '2' => 0];
            $referralByUser = [];
        }

        $fmtDtShort = static function (?string $dbValue): string {
            if (!$dbValue) {
                return '';
            }
            $ts = strtotime($dbValue);
            if (!$ts) {
                return (string) $dbValue;
            }
            return date('d/m/Y H:i', $ts);
        };

        $todoToday = [];
        if ($examTodoCount > 0) {
            $todoToday[] = [
                'label' => 'Passer vos examens',
                'count' => $examTodoCount,
                'meta' => $examPendingCount > 0
                    ? $examPendingCount . ' examen(s) en attente de correction'
                    : 'Examen(s) disponible(s) sur vos cours',
                'url' => $this->siteUrl('student/dashboard#exams'),
                'tone' => 'amber',
            ];
        }
        if ($unreadCount > 0) {
            $todoToday[] = [
                'label' => 'Lire vos notifications',
                'count' => $unreadCount,
                'meta' => 'Notification(s) non lue(s)',
                'url' => $this->siteUrl('notifications?space=student&scope=unread'),
                'tone' => 'rose',
            ];
        }
        if ($supportUnreadCount > 0) {
            $todoToday[] = [
                'label' => 'Répondre au support',
                'count' => $supportUnreadCount,
                'meta' => 'Message(s) support non lu(s)',
                'url' => $this->siteUrl('support?space=student'),
                'tone' => 'emerald',
            ];
        }
        if ($upcomingMeetingsCount > 0) {
            $todoToday[] = [
                'label' => 'Vérifier vos rendez-vous',
                'count' => $upcomingMeetingsCount,
                'meta' => 'Rendez-vous confirmé(s) à venir',
                'url' => $this->siteUrl('student/meetings'),
                'tone' => 'sky',
            ];
        }
        if ($activeEnrollments > 0) {
            $todoToday[] = [
                'label' => 'Continuer vos cours',
                'count' => $activeEnrollments,
                'meta' => 'Cours actif(s) à poursuivre',
                'url' => $this->siteUrl('student/my_courses'),
                'tone' => 'slate',
            ];
        }
        $todoToday = array_slice($todoToday, 0, 3);

        $continueItems = [];
        foreach (array_slice($recentEnrollments, 0, 3) as $row) {
            $totalLessons = max(0, (int) ($row['total_lessons'] ?? 0));
            $doneLessons = max(0, (int) ($row['done_lessons'] ?? 0));
            $progressPct = $totalLessons > 0 ? (int) round(($doneLessons / max(1, $totalLessons)) * 100) : 0;
            $progressPct = max(0, min(100, $progressPct));

            $levelTone = match (strtolower(trim((string) ($row['level'] ?? '')))) {
                'beginner' => 'blue',
                'intermediate' => 'emerald',
                'advanced' => 'violet',
                default => 'blue',
            };

            $continueItems[] = [
                'type' => 'course',
                'tone' => $levelTone,
                'icon' => 'book-open',
                'title' => trim((string) ($row['title'] ?? 'Cours')) ?: 'Cours',
                'meta' => $totalLessons > 0
                    ? $doneLessons . '/' . $totalLessons . ' leçons terminées'
                    : 'Progression en attente',
                'badge' => $totalLessons > 0 ? 'Cours suivi' : 'Cours',
                'progress_pct' => $progressPct,
                'done_lessons' => $doneLessons,
                'total_lessons' => $totalLessons,
                'remaining_lessons' => max(0, $totalLessons - $doneLessons),
                'url' => $this->siteUrl($resolveCourseUrl($row)),
                'cover_url' => $resolveCourseCoverUrl($row),
                'cover_alt' => trim((string) ($row['cover_alt'] ?? '')) ?: trim((string) ($row['title'] ?? 'Cours')),
            ];
        }

        if ($continueItems === []) {
            foreach ($todoToday as $item) {
                $count = max(0, (int) ($item['count'] ?? 0));
                $tone = trim((string) ($item['tone'] ?? 'blue'));
                $taskIcon = match ($tone) {
                    'rose' => 'bell',
                    'emerald' => 'lifebuoy',
                    'sky' => 'calendar',
                    'amber' => 'file-text',
                    default => 'book-open',
                };

                $continueItems[] = [
                    'type' => 'task',
                    'tone' => $tone,
                    'icon' => $taskIcon,
                    'title' => trim((string) ($item['label'] ?? 'Action')) ?: 'Action',
                    'meta' => trim((string) ($item['meta'] ?? '')) ?: 'Action prioritaire',
                    'badge' => $count > 0 ? (string) $count : 'À traiter',
                    'progress_pct' => max(18, min(100, $count * 24)),
                    'url' => trim((string) ($item['url'] ?? '')) ?: $this->siteUrl('student/dashboard'),
                    'cover_url' => '',
                    'cover_alt' => '',
                ];
            }
        }

        if ($continueItems === [] && $nextMeeting) {
            $meetingMode = strtolower(trim((string) ($nextMeeting['mode'] ?? '')));
            $meetingModeLabel = match ($meetingMode) {
                'onsite', 'presential', 'in_person' => 'Présentiel',
                'phone' => 'Téléphone',
                default => 'En ligne',
            };

            $continueItems[] = [
                'type' => 'meeting',
                'tone' => 'sky',
                'icon' => 'calendar',
                'title' => trim((string) ($nextMeeting['meeting_title'] ?? 'Rendez-vous')) ?: 'Rendez-vous',
                'meta' => $fmtDtShort(isset($nextMeeting['start_at']) ? (string) $nextMeeting['start_at'] : null) . ' · ' . $meetingModeLabel,
                'badge' => 'Session',
                'progress_pct' => 80,
                'url' => $this->siteUrl('student/meetings'),
                'cover_url' => '',
                'cover_alt' => '',
            ];
        }

        if ($continueItems === [] && $certificates !== []) {
            $latestCertificate = $certificates[0];
            $certificateCode = trim((string) ($latestCertificate['code'] ?? ''));
            $continueItems[] = [
                'type' => 'certificate',
                'tone' => 'emerald',
                'icon' => 'award',
                'title' => trim((string) ($latestCertificate['course_title'] ?? 'Certificat')) ?: 'Certificat',
                'meta' => 'Dernier document disponible',
                'badge' => 'Certificat',
                'progress_pct' => 100,
                'url' => $certificateCode !== ''
                    ? $this->siteUrl('certificate?code=' . urlencode($certificateCode))
                    : $this->siteUrl('student/certificates'),
                'cover_url' => '',
                'cover_alt' => '',
            ];
        }

        if ($continueItems === [] && $popularCourses !== []) {
            $course = $popularCourses[0];
            $continueItems[] = [
                'type' => 'course',
                'tone' => 'blue',
                'icon' => 'book-open',
                'title' => trim((string) ($course['title'] ?? 'Cours recommandé')) ?: 'Cours recommandé',
                'meta' => 'Cours mis en avant dans votre espace',
                'badge' => 'Explorer',
                'progress_pct' => 60,
                'url' => $this->siteUrl($resolveCourseUrl($course)),
                'cover_url' => $resolveCourseCoverUrl($course),
                'cover_alt' => trim((string) ($course['cover_alt'] ?? '')) ?: trim((string) ($course['title'] ?? 'Cours recommandé')),
            ];
        }

        $watchSeconds = 0;
        try {
            $watchSeconds = $this->model->sumLessonPositionSeconds($userId);
        } catch (\Throwable $e) {
            $watchSeconds = 0;
        }
        $watchHours = round($watchSeconds / 3600, 1);
        $watchHoursLabel = $this->formatHoursLabel($watchHours);

        $activityChartData = $this->buildLearningChartData($userId);
        $weekHours = array_sum(array_map(
            static fn ($value): float => (float) $value,
            is_array($activityChartData['week']['values'] ?? null) ? $activityChartData['week']['values'] : []
        ));
        $watchWeekHoursLabel = $weekHours > 0
            ? '≈ ' . $this->formatHoursLabel(round($weekHours, 1)) . ' cette semaine'
            : 'Aucune activité cette semaine';

        $heroSubtitle = '';
        $heroCourseTitle = '';
        $heroCourseRemainingLessons = 0;
        $primaryContinueItem = is_array($continueItems[0] ?? null) ? $continueItems[0] : [];
        if (($primaryContinueItem['type'] ?? '') === 'course') {
            $heroCourseTitle = trim((string) ($primaryContinueItem['title'] ?? ''));
            $heroCourseRemainingLessons = max(0, (int) ($primaryContinueItem['remaining_lessons'] ?? 0));
            if ($heroCourseTitle !== '') {
                if ($heroCourseRemainingLessons > 0) {
                    $heroSubtitle = 'Continuez votre formation "' . $heroCourseTitle . '". Il vous reste ' . $heroCourseRemainingLessons . ' module' . ($heroCourseRemainingLessons > 1 ? 's' : '') . ' à terminer cette semaine.';
                } else {
                    $heroSubtitle = 'Continuez votre formation "' . $heroCourseTitle . '".';
                }
            }
        }
        if ($heroSubtitle === '' && $nextMeeting) {
            $meetingLabel = trim((string) ($nextMeeting['course_title'] ?? '')) ?: trim((string) ($nextMeeting['meeting_title'] ?? 'Rendez-vous'));
            $meetingWhen = $fmtDtShort(isset($nextMeeting['start_at']) ? (string) $nextMeeting['start_at'] : null);
            $heroSubtitle = $meetingWhen !== ''
                ? 'Votre prochain rendez-vous pour "' . $meetingLabel . '" est confirmé le ' . $meetingWhen . '.'
                : 'Votre prochain rendez-vous pour "' . $meetingLabel . '" est confirmé.';
        }
        if ($heroSubtitle === '') {
            if ($activeEnrollments > 0) {
                $heroSubtitle = 'Vous suivez ' . $activeEnrollments . ' cours actif' . ($activeEnrollments > 1 ? 's' : '') . '.';
            } else {
                $heroSubtitle = 'Complétez votre profil et commencez votre premier cours depuis le catalogue.';
            }
        }

        $profileFields = [
            trim((string) ($user['first_name'] ?? '')) !== '',
            trim((string) ($user['last_name'] ?? '')) !== '',
            trim((string) ($user['email'] ?? '')) !== '',
            trim((string) ($user['phone'] ?? '')) !== '',
        ];
        $profileDone = count(array_filter($profileFields, static fn (bool $x): bool => $x));
        $profileCompletionPct = (int) round(($profileDone / max(1, count($profileFields))) * 100);

        $onboardingSteps = [
            [
                'label' => 'Explorer le catalogue',
                'done' => $viewedCoursesCount > 0,
                'cta' => $this->siteUrl('catalogue'),
            ],
            [
                'label' => 'Obtenir votre premier cours',
                'done' => $paidOrdersCount > 0 || $activeEnrollments > 0,
                'cta' => $this->siteUrl('catalogue'),
            ],
            [
                'label' => 'Lancer un premier examen',
                'done' => $examAttemptsCount > 0,
                'cta' => $this->siteUrl('student/my_courses'),
            ],
            [
                'label' => 'Compléter le profil',
                'done' => $profileCompletionPct >= 75,
                'cta' => $this->siteUrl('profile'),
            ],
        ];
        $onboardingDone = count(array_filter($onboardingSteps, static fn (array $step): bool => (bool) ($step['done'] ?? false)));
        $onboardingTotal = count($onboardingSteps);
        $onboardingPct = (int) round(($onboardingDone / max(1, $onboardingTotal)) * 100);

        $isNewStudent = $activeEnrollments === 0
            && $paidOrdersCount === 0
            && $examAttemptsCount === 0
            && $completedCoursesCount === 0
            && $upcomingMeetingsCount === 0;
        $dashboardMode = $isNewStudent ? 'onboarding' : 'active';

        return [
            'user' => $user,
            'unreadCount' => $unreadCount,
            'supportUnreadCount' => $supportUnreadCount,
            'notifications' => $notifications,
            'activeEnrollments' => $activeEnrollments,
            'activeEnrollmentsThisMonth' => $activeEnrollmentsThisMonth,
            'upcomingMeetingsCount' => $upcomingMeetingsCount,
            'examTodoCount' => $examTodoCount,
            'examPendingCount' => $examPendingCount,
            'examGradedCount' => $examGradedCount,
            'examItems' => $examItems,
            'examMeetingById' => $examMeetingById,
            'certificates' => $certificates,
            'certCount' => $certCount,
            'referralCode' => $referralCode,
            'referralLink' => $referralLink,
            'canShareReferral' => $canShareReferral,
            'referralEarnings' => $referralEarnings,
            'earnedByLevel' => $earnedByLevel,
            'referralByUser' => $referralByUser,
            'todoToday' => $todoToday,
            'continueItems' => $continueItems,
            'fmtDtShort' => $fmtDtShort,
            'popularCourses' => $popularCourses,
            'recentActivity' => $recentActivity,
            'nextMeeting' => $nextMeeting,
            'watchSeconds' => $watchSeconds,
            'watchHours' => $watchHours,
            'watchHoursLabel' => $watchHoursLabel,
            'watchWeekHoursLabel' => $watchWeekHoursLabel,
            'overallProgressPct' => $overallProgressPct,
            'heroSubtitle' => $heroSubtitle,
            'heroCourseTitle' => $heroCourseTitle,
            'heroCourseRemainingLessons' => $heroCourseRemainingLessons,
            'activityChartData' => $activityChartData,
            'dashboardMode' => $dashboardMode,
            'onboardingState' => [
                'steps' => $onboardingSteps,
                'done' => $onboardingDone,
                'total' => $onboardingTotal,
                'progress_pct' => $onboardingPct,
            ],
            'studentKpi' => [
                'paid_orders' => $paidOrdersCount,
                'completed_courses' => $completedCoursesCount,
                'viewed_courses' => $viewedCoursesCount,
                'exam_attempts' => $examAttemptsCount,
                'profile_completion_pct' => $profileCompletionPct,
            ],
        ];
    }

    /**
     * @return array{week:array{labels:array<int,string>,values:array<int,float>,max:float},month:array{labels:array<int,string>,values:array<int,float>,max:float}}
     */
    private function buildLearningChartData(int $userId): array
    {
        $today = new \DateTimeImmutable('today');
        $fromDate = $today->modify('-34 days')->setTime(0, 0)->format('Y-m-d H:i:s');
        $dailyTotals = [];

        try {
            foreach ($this->model->fetchLessonPositionDailyTotals($userId, $fromDate) as $row) {
                $dayKey = trim((string) ($row['day_key'] ?? ''));
                if ($dayKey === '') {
                    continue;
                }
                $dailyTotals[$dayKey] = max(0, (int) ($row['total_seconds'] ?? 0));
            }
        } catch (\Throwable $e) {
            $dailyTotals = [];
        }

        $weekStart = $today->modify('monday this week')->setTime(0, 0);
        $weekLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $weekValues = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $weekStart->modify('+' . $i . ' days');
            $dayKey = $day->format('Y-m-d');
            $weekValues[] = round(((int) ($dailyTotals[$dayKey] ?? 0)) / 3600, 1);
        }

        $monthValues = [0.0, 0.0, 0.0, 0.0];
        $currentMonth = $today->format('Y-m');
        foreach ($dailyTotals as $dayKey => $seconds) {
            if (strpos($dayKey, $currentMonth) !== 0) {
                continue;
            }

            try {
                $day = new \DateTimeImmutable($dayKey);
            } catch (\Throwable $e) {
                continue;
            }

            $dayOfMonth = (int) $day->format('j');
            $bucket = (int) floor(max(1, $dayOfMonth - 1) / 7);
            if ($bucket < 0) {
                $bucket = 0;
            } elseif ($bucket > 3) {
                $bucket = 3;
            }

            $monthValues[$bucket] += ((int) $seconds) / 3600;
        }
        $monthValues = array_map(static fn (float $value): float => round($value, 1), $monthValues);

        return [
            'week' => [
                'labels' => $weekLabels,
                'values' => $weekValues,
                'max' => $this->chartAxisMax($weekValues),
            ],
            'month' => [
                'labels' => ['Sem. 1', 'Sem. 2', 'Sem. 3', 'Sem. 4'],
                'values' => $monthValues,
                'max' => $this->chartAxisMax($monthValues),
            ],
        ];
    }

    /**
     * @param array<int,float> $values
     */
    private function chartAxisMax(array $values): float
    {
        $maxValue = 0.0;
        foreach ($values as $value) {
            $maxValue = max($maxValue, (float) $value);
        }

        $axisMax = (float) ceil(($maxValue + 0.25) * 2) / 2;
        if ($axisMax < 4.5) {
            $axisMax = 4.5;
        }

        return $axisMax;
    }

    private function formatHoursLabel(float $hours): string
    {
        $label = rtrim(rtrim(number_format(max(0.0, $hours), 1, '.', ''), '0'), '.');
        if ($label === '') {
            $label = '0';
        }

        return $label . 'h';
    }
}
