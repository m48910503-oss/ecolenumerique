<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;

use App\Models\TeacherCoursePreviewModel;

final class CoursePreviewService extends Service
{
    private TeacherCoursePreviewModel $model;

    public function __construct(TeacherCoursePreviewModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param list<string> $roles
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array{
     *   httpStatus:?int,
     *   abortMessage:?string,
     *   redirectTo:?string,
     *   view:array<string,mixed>
     * }
     */
    public function build(array $user, array $roles, array $get, string $method = 'GET', array $post = []): array
    {
        $courseId = (int) ($get['id'] ?? 0);
        if ($courseId <= 0) {
            return $this->abort(404, 'Cours introuvable.');
        }

        $course = $this->model->findCourseById($courseId);
        if (!$course) {
            return $this->abort(404, 'Cours introuvable.');
        }

        $isAdmin = in_array('admin', $roles, true);
        if (!$isAdmin && (int) ($course['teacher_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            return $this->abort(403, "Acc\u{00e8}s refus\u{00e9}.");
        }

        $moderationErrors = [];
        $moderationSuccess = (string) $this->flashGet('moderation_success', '');
        $ratingsModerationReady = $this->model->ratingsModerationReady();
        $hasModerationPost = false;

        if (strtoupper($method) === 'POST') {
            $action = (string) ($post['action'] ?? '');
            if ($action === 'hide_review' || $action === 'unhide_review') {
                $hasModerationPost = true;
                if (!$ratingsModerationReady) {
                    $moderationErrors[] = 'Module indisponible: appliquez database/schema_v42.sql puis rechargez la page.';
                } else {
                    $ratingId = (int) ($post['rating_id'] ?? 0);
                    if ($ratingId <= 0) {
                        $moderationErrors[] = 'Avis invalide.';
                    } else {
                        $review = $this->model->findCourseRatingForModeration($courseId, $ratingId);
                        if (!$review) {
                            $moderationErrors[] = 'Avis introuvable.';
                        } else {
                            $mustHide = $action === 'hide_review';
                            $alreadyHidden = (int) ($review['is_hidden_by_teacher'] ?? 0) === 1;

                            if ($mustHide === $alreadyHidden) {
                                $moderationSuccess = $mustHide ? "Avis d\u{00e9}j\u{00e0} masqu\u{00e9}." : "Avis d\u{00e9}j\u{00e0} visible.";
                            } else {
                                $this->model->updateCourseRatingModerationState(
                                    $ratingId,
                                    $mustHide,
                                    (int) ($user['id'] ?? 0)
                                );
                                $moderationSuccess = $mustHide ? "Avis masqu\u{00e9}." : 'Avis rendu visible.';
                            }
                        }
                    }
                }
            }
        }

        if (strtoupper($method) === 'POST' && $hasModerationPost && $moderationErrors === [] && $moderationSuccess !== '') {
            $this->flashSet('moderation_success', $moderationSuccess);
            return [
                'httpStatus' => null,
                'abortMessage' => null,
                'redirectTo' => $this->siteUrl('teacher/course_preview?id=' . $courseId . '#reviews-moderation'),
                'view' => [],
            ];
        }

        $isLocked = in_array((string) ($course['status'] ?? ''), ['submitted', 'approved'], true);
        $statusLabels = [
            'draft' => 'Brouillon',
            'submitted' => 'Soumis',
            'approved' => 'Approuve',
            'rejected' => 'Rejete',
        ];
        $levelLabels = [
            'beginner' => 'Débutant',
            'intermediate' => 'Intermédiaire',
            'advanced' => 'Avancé',
        ];

        $extractYoutubeId = static function (string $url): ?string {
            $parts = parse_url($url);
            if (!$parts || empty($parts['host'])) {
                return null;
            }
            $host = strtolower((string) $parts['host']);
            if ($host === 'youtu.be') {
                return ltrim((string) ($parts['path'] ?? ''), '/');
            }
            if (str_contains($host, 'youtube.com') && !empty($parts['query'])) {
                parse_str((string) $parts['query'], $q);
                return is_string($q['v'] ?? null) ? $q['v'] : null;
            }
            return null;
        };

        $docLessonIdFromMime = static function (?string $mime): int {
            if (!$mime) {
                return 0;
            }
            if (preg_match('/\\|lesson:(\\d+)/', $mime, $m)) {
                return (int) $m[1];
            }
            return 0;
        };

        $docLabel = static function (?string $type, ?string $mime): string {
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
        };

        $formatCourseList = static function ($value): string {
            if (is_array($value)) {
                $list = array_map('trim', $value);
            } else {
                $decoded = json_decode((string) $value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $list = array_map('trim', $decoded);
                } else {
                    return nl2br(e((string) $value));
                }
            }
            $list = array_values(array_filter($list, static fn($x) => $x !== ''));
            if ($list === []) {
                return '';
            }
            $items = array_map(static fn($item) => '<li>' . e((string) $item) . '</li>', $list);
            return '<ul class="list-disc list-inside space-y-1">' . implode('', $items) . '</ul>';
        };

        $chapters = $this->model->fetchChaptersByCourseId($courseId);
        $lessonsByChapter = [];
        $docsByChapter = [];
        $docsByLesson = [];
        $totalMin = 0;

        if ($chapters !== []) {
            $chapterIds = array_values(array_map(static fn($c) => (int) $c['id'], $chapters));

            foreach ($this->model->fetchLessonsByChapterIds($chapterIds) as $lesson) {
                $chapterId = (int) ($lesson['chapter_id'] ?? 0);
                $lessonsByChapter[$chapterId][] = $lesson;
                if (!is_null($lesson['duration_estimate_min'] ?? null)) {
                    $totalMin += (int) $lesson['duration_estimate_min'];
                }
            }

            foreach ($this->model->fetchDocumentsByChapterIds($chapterIds) as $doc) {
                $lessonId = $docLessonIdFromMime(is_string($doc['mime'] ?? null) ? (string) $doc['mime'] : null);
                if ($lessonId > 0) {
                    $docsByLesson[$lessonId][] = $doc;
                } else {
                    $docsByChapter[(int) ($doc['chapter_id'] ?? 0)][] = $doc;
                }
            }
        }

        $durationLabel = null;
        if ($totalMin > 0) {
            $durationLabel = \format_duration_minutes_hm($totalMin);
        } elseif (!empty($course['duration_hours'])) {
            $minutes = (int) round(((float) $course['duration_hours']) * 60);
            if ($minutes > 0) {
                $durationLabel = \format_duration_minutes_hm($minutes);
            }
        }

        $videoPlaylist = [];
        foreach ($lessonsByChapter as $chapterId => $lessonList) {
            foreach ($lessonList as $lesson) {
                $isVideo = (string) ($lesson['lesson_type'] ?? '') === 'video';
                $youtubeUrl = (string) ($lesson['youtube_url'] ?? '');
                if (!$isVideo || $youtubeUrl === '') {
                    continue;
                }
                $ytId = $extractYoutubeId($youtubeUrl);
                if (!$ytId) {
                    continue;
                }
                $videoPlaylist[] = [
                    'id' => (int) ($lesson['id'] ?? 0),
                    'title' => (string) ($lesson['title'] ?? ''),
                    'duration' => $lesson['duration_estimate_min'] ?? null,
                    'yt_id' => $ytId,
                    'chapter' => (int) $chapterId,
                ];
            }
        }

        $meetings = $this->model->fetchMeetingsByCourseId($courseId);

        $docTotals = ['exercise' => 0, 'resource' => 0, 'exam' => 0, 'all' => 0];
        foreach ($docsByChapter as $items) {
            foreach ($items as $doc) {
                $type = (string) ($doc['type'] ?? '');
                $docTotals['all']++;
                if (isset($docTotals[$type])) {
                    $docTotals[$type]++;
                }
            }
        }
        foreach ($docsByLesson as $items) {
            foreach ($items as $doc) {
                $type = (string) ($doc['type'] ?? '');
                $docTotals['all']++;
                if (isset($docTotals[$type])) {
                    $docTotals[$type]++;
                }
            }
        }

        $exams = $this->model->fetchExamsByCourseId($courseId);
        $ratingsForModeration = $this->model->fetchCourseRatingsForModeration($courseId, 60);
        $hiddenReviewCount = 0;
        foreach ($ratingsForModeration as $ratingRow) {
            if ((int) ($ratingRow['is_hidden_by_teacher'] ?? 0) === 1) {
                $hiddenReviewCount++;
            }
        }
        $visibleReviewCount = max(0, count($ratingsForModeration) - $hiddenReviewCount);

        $dashboardUrl = $this->siteUrl('student/dashboard');
        if ($isAdmin) {
            $dashboardUrl = $this->siteUrl('admin/dashboard');
        } elseif (in_array('teacher', $roles, true)) {
            $dashboardUrl = $this->siteUrl('teacher/dashboard');
        }

        return [
            'httpStatus' => null,
            'abortMessage' => null,
            'redirectTo' => null,
            'view' => [
                'courseId' => $courseId,
                'course' => $course,
                'isLocked' => $isLocked,
                'statusLabels' => $statusLabels,
                'levelLabels' => $levelLabels,
                'chapters' => $chapters,
                'lessonsByChapter' => $lessonsByChapter,
                'docsByChapter' => $docsByChapter,
                'docsByLesson' => $docsByLesson,
                'durationLabel' => $durationLabel,
                'videoPlaylist' => $videoPlaylist,
                'meetings' => $meetings,
                'docTotals' => $docTotals,
                'exams' => $exams,
                'ratingsModerationReady' => $ratingsModerationReady,
                'ratingsForModeration' => $ratingsForModeration,
                'visibleReviewCount' => $visibleReviewCount,
                'hiddenReviewCount' => $hiddenReviewCount,
                'moderationErrors' => $moderationErrors,
                'moderationSuccess' => $moderationSuccess,
                'dashboardUrl' => $dashboardUrl,
                'extractYoutubeId' => $extractYoutubeId,
                'docLabel' => $docLabel,
                'formatCourseList' => $formatCourseList,
            ],
        ];
    }

    /**
     * @return array{httpStatus:int,abortMessage:string,redirectTo:?string,view:array<string,mixed>}
     */
    private function abort(int $status, string $message): array
    {
        return [
            'httpStatus' => $status,
            'abortMessage' => $message,
            'redirectTo' => null,
            'view' => [],
        ];
    }
}

