<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;
use App\Models\AdminCoursePreviewModel;

final class CoursePreviewService extends Service
{
    private AdminCoursePreviewModel $model;

    public function __construct(AdminCoursePreviewModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array{
     *   httpStatus:?int,
     *   abortMessage:?string,
     *   redirectTo:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(array $user, string $method, array $get, array $post): array
    {
        $courseId = (int) ($get['id'] ?? 0);
        if ($courseId <= 0) {
            return $this->abort(404, 'Cours introuvable.');
        }

        $course = $this->model->findCourseWithTeacherById($courseId);
        if (!$course) {
            return $this->abort(404, 'Cours introuvable.');
        }

        $errors = [];
        $success = (string) $this->flashGet('success', '');

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');
            $reason = trim((string) ($post['reason'] ?? ''));

            if ((string) ($course['status'] ?? '') !== 'submitted') {
                $errors[] = "Ce cours n'est plus en attente.";
            } else {
                try {
                    $this->model->beginTransaction();

                    if ($action === 'approve') {
                        $this->model->markCourseApproved($courseId);
                        $this->model->insertNotification(
                            (int) ($course['teacher_user_id'] ?? 0),
                            'Cours approuvé',
                            ((string) ($course['title'] ?? 'Cours')) . ' a été approuvé.',
                            $this->siteUrl('teacher/courses')
                        );
                        $this->model->insertAudit((int) ($user['id'] ?? 0), 'course_approve', $courseId);
                        $success = 'Cours approuvé.';
                        $course['status'] = 'approved';
                        $course['last_rejection_reason'] = null;
                    } elseif ($action === 'reject') {
                        if ($reason === '') {
                            throw new \RuntimeException('Le motif du rejet est obligatoire.');
                        }

                        $this->model->markCourseRejected($courseId, $reason);
                        $this->model->insertNotification(
                            (int) ($course['teacher_user_id'] ?? 0),
                            'Cours rejeté',
                            ((string) ($course['title'] ?? 'Cours')) . ' a été rejeté. Motif : ' . $reason,
                            $this->siteUrl('teacher/courses')
                        );
                        $this->model->insertAudit((int) ($user['id'] ?? 0), 'course_reject', $courseId);
                        $success = 'Cours rejeté.';
                        $course['status'] = 'rejected';
                        $course['last_rejection_reason'] = $reason;
                    } else {
                        throw new \RuntimeException('Action invalide.');
                    }

                    $this->model->commit();
                } catch (\Throwable $e) {
                    if ($this->model->inTransaction()) {
                        $this->model->rollBack();
                    }
                    $errors[] = 'Erreur : ' . $e->getMessage();
                }
            }
        }

        if ($method === 'POST' && $errors === [] && $success !== '') {
            $this->flashSet('success', $success);
            return [
                'httpStatus' => null,
                'abortMessage' => null,
                'redirectTo' => $this->siteUrl('admin/course_preview?id=' . $courseId),
                'view' => [],
            ];
        }

        $docLessonIdFromMime = static function (?string $mime): int {
            if (!$mime) {
                return 0;
            }
            if (preg_match('/\|lesson:(\d+)/', $mime, $matches) === 1) {
                return (int) ($matches[1] ?? 0);
            }
            return 0;
        };

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
                parse_str((string) $parts['query'], $query);
                return is_string($query['v'] ?? null) ? $query['v'] : null;
            }
            return null;
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

            $list = array_values(array_filter($list, static fn($item): bool => $item !== ''));
            if ($list === []) {
                return '';
            }

            $items = array_map(
                static fn($item): string => '<li>' . e((string) $item) . '</li>',
                $list
            );

            return '<ul class="list-disc list-inside space-y-1">' . implode('', $items) . '</ul>';
        };

        $chapters = $this->model->fetchChaptersByCourseId($courseId);
        $lessonsByChapter = [];
        $docsByChapter = [];
        $docsByLesson = [];
        $totalMin = 0;

        if ($chapters !== []) {
            $chapterIds = array_values(array_map(static fn($chapter): int => (int) ($chapter['id'] ?? 0), $chapters));

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
                    $chapterId = (int) ($doc['chapter_id'] ?? 0);
                    $docsByChapter[$chapterId][] = $doc;
                }
            }
        }

        $durationLabel = $this->durationLabel($totalMin, (float) ($course['duration_hours'] ?? 0.0));

        $videoPlaylist = [];
        foreach ($lessonsByChapter as $chapterId => $lessonList) {
            foreach ($lessonList as $lesson) {
                $isVideo = (string) ($lesson['lesson_type'] ?? '') === 'video';
                $youtubeUrl = (string) ($lesson['youtube_url'] ?? '');
                if (!$isVideo || $youtubeUrl === '') {
                    continue;
                }

                $youtubeId = $extractYoutubeId($youtubeUrl);
                if ($youtubeId === null || $youtubeId === '') {
                    continue;
                }

                $videoPlaylist[] = [
                    'id' => (int) ($lesson['id'] ?? 0),
                    'title' => (string) ($lesson['title'] ?? ''),
                    'duration' => $lesson['duration_estimate_min'] ?? null,
                    'yt_id' => $youtubeId,
                    'chapter' => (int) $chapterId,
                ];
            }
        }

        $meetings = $this->model->fetchMeetingsByCourseId($courseId);
        $exams = $this->model->fetchExamsByCourseId($courseId);

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

        return [
            'httpStatus' => null,
            'abortMessage' => null,
            'redirectTo' => null,
            'view' => [
                'courseId' => $courseId,
                'course' => $course,
                'errors' => $errors,
                'success' => $success,
                'chapters' => $chapters,
                'lessonsByChapter' => $lessonsByChapter,
                'docsByChapter' => $docsByChapter,
                'docsByLesson' => $docsByLesson,
                'meetings' => $meetings,
                'exams' => $exams,
                'durationLabel' => $durationLabel,
                'videoPlaylist' => $videoPlaylist,
                'docTotals' => $docTotals,
                'statusLabels' => [
                    'draft' => 'Brouillon',
                    'submitted' => 'Soumis',
                    'approved' => 'Approuvé',
                    'rejected' => 'Rejeté',
                    'archived' => 'Archivé',
                ],
                'levelLabels' => [
                    'beginner' => 'Débutant',
                    'intermediate' => 'Intermédiaire',
                    'advanced' => 'Avancé',
                ],
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

    private function durationLabel(int $totalMin, float $durationHours): ?string
    {
        if ($totalMin > 0) {
            return \format_duration_minutes_hm($totalMin);
        }

        $fallbackMin = (int) round($durationHours * 60);
        if ($fallbackMin <= 0) {
            return null;
        }

        return \format_duration_minutes_hm($fallbackMin);
    }
}
