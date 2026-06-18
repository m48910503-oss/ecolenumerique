<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Service;

use App\Models\ApiLearningModel;

final class LearningService extends Service
{
    private ApiLearningModel $model;

    public function __construct(ApiLearningModel $model)
    {
        $this->model = $model;
    }

    public function handleNotes(string $method, int $lessonId): array
    {
        if ($method === 'GET') {
            return ['status' => 200, 'payload' => ['ok' => true, 'notes' => [], 'lesson_id' => $lessonId]];
        }

        if ($method === 'POST') {
            if ($lessonId < 1) {
                return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'missing_lesson']];
            }
            return ['status' => 200, 'payload' => ['ok' => true, 'id' => 0, 'lesson_id' => $lessonId]];
        }

        return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'bad_request']];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $input
     */
    public function handleBookmarks(array $user, string $method, int $lessonId, array $input = []): array
    {
        if ($lessonId < 1) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'missing_lesson']];
        }

        $lesson = $this->model->findLessonCourseByLessonId($lessonId);
        if (!$lesson) {
            return ['status' => 404, 'payload' => ['ok' => false, 'error' => 'lesson_not_found']];
        }

        $courseId = (int) ($lesson['course_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1 || !$this->model->hasActiveEnrollment($userId, $courseId)) {
            return ['status' => 403, 'payload' => ['ok' => false, 'error' => 'no_access']];
        }

        if ($method === 'GET') {
            return [
                'status' => 200,
                'payload' => [
                    'ok' => true,
                    'lesson_id' => $lessonId,
                    'bookmarks' => $this->model->fetchLessonBookmarks($userId, $lessonId),
                ],
            ];
        }

        if ($method === 'POST') {
            $action = strtolower(trim((string) ($input['action'] ?? 'create')));
            if ($action === 'delete') {
                $bookmarkId = (int) ($input['bookmark_id'] ?? 0);
                if ($bookmarkId < 1) {
                    return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'missing_bookmark_id']];
                }
                $deleted = $this->model->deleteLessonBookmark($userId, $bookmarkId, $lessonId);
                return [
                    'status' => 200,
                    'payload' => [
                        'ok' => true,
                        'deleted' => $deleted,
                        'bookmark_id' => $bookmarkId,
                        'lesson_id' => $lessonId,
                    ],
                ];
            }

            $timestamp = max(0, (int) ($input['timestamp'] ?? 0));
            $label = trim((string) ($input['label'] ?? ''));
            if ($label === '') {
                $label = 'Favori ' . $this->formatTimestamp($timestamp);
            }
            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                if (mb_strlen($label) > 120) {
                    $label = mb_substr($label, 0, 120);
                }
            } elseif (strlen($label) > 120) {
                $label = substr($label, 0, 120);
            }

            $bookmarkId = $this->model->insertLessonBookmark(
                $userId,
                $courseId,
                $lessonId,
                $timestamp,
                $label
            );

            return [
                'status' => 200,
                'payload' => [
                    'ok' => true,
                    'id' => $bookmarkId,
                    'lesson_id' => $lessonId,
                    'timestamp' => $timestamp,
                    'label' => $label,
                ],
            ];
        }

        return ['status' => 405, 'payload' => ['ok' => false, 'error' => 'method_not_allowed']];
    }

    public function handleProgress(array $user, string $method, string $action, int $lessonId): array
    {
        if ($method !== 'POST') {
            return ['status' => 405, 'payload' => ['error' => 'method_not_allowed']];
        }
        if ($lessonId < 1) {
            return ['status' => 400, 'payload' => ['error' => 'lesson_id_requis']];
        }

        $lesson = $this->model->findLessonCourseByLessonId($lessonId);
        if (!$lesson) {
            return ['status' => 404, 'payload' => ['error' => 'lecon_introuvable']];
        }

        $courseId = (int) ($lesson['course_id'] ?? 0);
        if (!$this->model->hasActiveEnrollment((int) $user['id'], $courseId)) {
            return ['status' => 403, 'payload' => ['error' => 'non_inscrit']];
        }

        if ($action === 'complete') {
            $this->model->insertLessonCompletion((int) $user['id'], $courseId, $lessonId);
        }

        $total = $this->model->countCourseLessonsDirect($courseId);
        $done = $this->model->countLessonCompletions((int) $user['id'], $courseId);
        $done = $total > 0 ? min($done, $total) : 0;
        $percent = $total > 0 ? (int) floor(($done / $total) * 100) : 0;
        $percent = max(0, min(100, $percent));

        return [
            'status' => 200,
            'payload' => [
                'ok' => true,
                'done' => $done,
                'total' => $total,
                'percent' => $percent,
            ],
        ];
    }

    public function handleProgressPosition(array $user, string $method, int $lessonId, array $post): array
    {
        if ($lessonId < 1) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'missing_lesson']];
        }

        if ($method === 'GET') {
            $seconds = $this->model->fetchLessonPositionSeconds((int) $user['id'], $lessonId);
            return ['status' => 200, 'payload' => ['ok' => true, 'seconds' => $seconds]];
        }

        if ($method !== 'POST') {
            return ['status' => 405, 'payload' => ['ok' => false, 'error' => 'method_not_allowed']];
        }

        $seconds = max(0, (int) ($post['seconds'] ?? 0));
        $duration = max(0, (int) ($post['duration'] ?? 0));
        $percent = max(0, min(100, (int) ($post['percent'] ?? 0)));

        $lesson = $this->model->findLessonCourseByLessonId($lessonId);
        if (!$lesson) {
            return ['status' => 404, 'payload' => ['ok' => false, 'error' => 'lesson_not_found']];
        }
        $courseId = (int) ($lesson['course_id'] ?? 0);

        if (!$this->model->hasActiveEnrollment((int) $user['id'], $courseId)) {
            return ['status' => 403, 'payload' => ['ok' => false, 'error' => 'no_access']];
        }

        $this->model->upsertLessonPosition(
            (int) $user['id'],
            $courseId,
            $lessonId,
            $seconds,
            $duration,
            $percent
        );

        return ['status' => 200, 'payload' => ['ok' => true]];
    }

    public function handleLessonComplete(array $user, string $method, array $data): array
    {
        if ($method !== 'POST') {
            return ['status' => 405, 'payload' => ['status' => 'error', 'message' => 'Méthode non autorisée']];
        }

        $courseId = isset($data['course_id']) ? (int) $data['course_id'] : 0;
        $lessonId = isset($data['lesson_id']) ? (int) $data['lesson_id'] : 0;
        if ($courseId < 1 || $lessonId < 1) {
            return ['status' => 400, 'payload' => ['status' => 'error', 'message' => 'Paramètres invalides.']];
        }

        if (!$this->model->hasActiveEnrollment((int) $user['id'], $courseId)) {
            return ['status' => 403, 'payload' => ['status' => 'error', 'message' => 'Accès au cours non autorisé.']];
        }

        $this->model->insertLessonCompletion((int) $user['id'], $courseId, $lessonId);

        $totalLessons = $this->model->countCourseLessonsByChapters($courseId);
        $doneCount = $this->model->countLessonCompletions((int) $user['id'], $courseId);
        $doneCount = $totalLessons > 0 ? min($doneCount, $totalLessons) : 0;
        $percent = $totalLessons > 0 ? (int) floor(($doneCount / $totalLessons) * 100) : 0;
        $percent = max(0, min(100, $percent));

        return [
            'status' => 200,
            'payload' => [
                'status' => 'ok',
                'message' => 'Leçon marquée comme complétée.',
                'progress_percent' => $percent,
                'done_count' => $doneCount,
                'total_lessons' => $totalLessons,
                'lesson_id' => $lessonId,
            ],
        ];
    }

    private function formatTimestamp(int $seconds): string
    {
        $safe = max(0, $seconds);
        $hours = intdiv($safe, 3600);
        $minutes = intdiv($safe % 3600, 60);
        $secs = $safe % 60;
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%02d:%02d', $minutes, $secs);
    }
}

