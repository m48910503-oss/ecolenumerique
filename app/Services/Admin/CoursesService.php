<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;
use App\Models\AdminCoursesModel;
use App\Support\Pagination;

final class CoursesService extends Service
{
    private AdminCoursesModel $model;

    public function __construct(AdminCoursesModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(array $user, string $method, array $get, array $post): array
    {
        $errors = [];
        $success = (string) $this->flashGet('success', '');

        $q = trim((string) ($get['q'] ?? $post['q'] ?? ''));
        $teacher = trim((string) ($get['teacher'] ?? $post['teacher'] ?? ''));
        $category = trim((string) ($get['category'] ?? $post['category'] ?? ''));
        $dateFrom = trim((string) ($get['date_from'] ?? $post['date_from'] ?? ''));
        $dateTo = trim((string) ($get['date_to'] ?? $post['date_to'] ?? ''));
        $teacherId = max(0, (int) ($get['teacher_id'] ?? $post['teacher_id'] ?? 0));

        $allowedSearchTypes = ['name', 'teacher', 'category', 'date'];
        $searchTypeRaw = strtolower(trim((string) ($get['search_type'] ?? $post['search_type'] ?? '')));
        if ($searchTypeRaw === '') {
            if ($dateFrom !== '' || $dateTo !== '') {
                $searchTypeRaw = 'date';
            } elseif ($teacher !== '') {
                $searchTypeRaw = 'teacher';
            } elseif ($category !== '') {
                $searchTypeRaw = 'category';
            } else {
                $searchTypeRaw = 'name';
            }
        }
        $searchType = in_array($searchTypeRaw, $allowedSearchTypes, true) ? $searchTypeRaw : 'name';

        $filters = [
            'q' => $q,
            'teacher' => $teacher,
            'category' => $category,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'teacher_id' => $teacherId,
            'search_type' => $searchType,
        ];

        if ($searchType === 'name') {
            $filters['teacher'] = '';
            $filters['category'] = '';
            $filters['date_from'] = '';
            $filters['date_to'] = '';
        } elseif ($searchType === 'teacher') {
            $filters['q'] = '';
            $filters['category'] = '';
            $filters['date_from'] = '';
            $filters['date_to'] = '';
        } elseif ($searchType === 'category') {
            $filters['q'] = '';
            $filters['teacher'] = '';
            $filters['date_from'] = '';
            $filters['date_to'] = '';
        } elseif ($searchType === 'date') {
            $filters['q'] = '';
            $filters['teacher'] = '';
            $filters['category'] = '';
        }

        $currentStatusTab = strtolower(trim((string) ($get['tab'] ?? $post['tab'] ?? 'submitted')));
        if (!in_array($currentStatusTab, ['submitted', 'approved'], true)) {
            $currentStatusTab = 'submitted';
        }

        $requestedPage = (int) ($get['page'] ?? $post['page'] ?? 1);
        $page = max(1, $requestedPage);
        $perPage = 10;

        if ($method === 'POST') {
            $action = strtolower(trim((string) ($post['action'] ?? '')));
            if (in_array($action, ['approve', 'reject', 'correction', 'remove'], true)) {
                $errors = $this->handleSingleAction($user, $post, $action, $success);
            } elseif (in_array($action, ['bulk_approve', 'bulk_reject', 'bulk_correction'], true)) {
                $errors = $this->handleBulkAction($user, $post, $action, $success);
            } else {
                $errors[] = 'Action invalide.';
            }
        }

        if ($method === 'POST' && $errors === [] && $success !== '') {
            $this->flashSet('success', $success);
            $query = [];
            foreach ($filters as $key => $value) {
                if ($value !== '') {
                    $query[$key] = $value;
                }
            }
            $query['tab'] = $currentStatusTab;
            if ($page > 1) {
                $query['page'] = $page;
            }
            $redirect = 'admin/courses' . ($query !== [] ? ('?' . http_build_query($query)) : '');
            return [
                'redirectTo' => $this->siteUrl($redirect),
                'view' => [],
            ];
        }

        $pendingTotalCount = $this->model->fetchPendingCoursesTotal($filters);
        $approvedTotalCount = $this->model->fetchHistoryCoursesTotal($filters);

        $activeTotalCount = $currentStatusTab === 'approved' ? $approvedTotalCount : $pendingTotalCount;
        $pagination = Pagination::build($activeTotalCount, $page, $perPage);
        $page = (int) $pagination['page'];
        $offset = (int) $pagination['offset'];

        $courseRows = $currentStatusTab === 'approved'
            ? $this->model->fetchApprovedCourses($perPage, $offset, $filters)
            : $this->model->fetchPendingCourses($perPage, $offset, $filters);

        $courseStats = $this->model->fetchCourseStatsForIds($this->extractCourseIds($courseRows, []));
        $courseRows = $this->attachCourseStats($courseRows, $courseStats);
        $moderationSummary = $this->model->fetchModerationSummary();

        $statusLabels = [
            'draft' => 'Brouillon',
            'submitted' => 'Soumis',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'archived' => 'Archivé',
        ];

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'success' => $success,
                'focusCourse' => null,
                'focusCourseId' => 0,
                'courseRows' => $courseRows,
                'currentStatusTab' => $currentStatusTab,
                'pendingDetails' => [],
                'statusLabels' => $statusLabels,
                'moderationSummary' => $moderationSummary,
                'filters' => $filters,
                'pendingTotalCount' => $pendingTotalCount,
                'approvedTotalCount' => $approvedTotalCount,
                'pagination' => [
                    'page' => $page,
                    'perPage' => $perPage,
                    'total' => $activeTotalCount,
                    'totalPages' => (int) $pagination['total_pages'],
                    'hasPrev' => (bool) $pagination['has_prev'],
                    'hasNext' => (bool) $pagination['has_next'],
                    'start' => (int) $pagination['start'],
                    'end' => (int) $pagination['end'],
                    'total_pages' => (int) $pagination['total_pages'],
                    'per_page' => (int) $pagination['per_page'],
                    'has_prev' => (bool) $pagination['has_prev'],
                    'has_next' => (bool) $pagination['has_next'],
                ],
            ],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $pendingCourses
     * @param array<int,array<string,mixed>> $historyCourses
     * @return array<int,int>
     */
    private function extractCourseIds(array $pendingCourses, array $historyCourses): array
    {
        $ids = [];
        foreach ([$pendingCourses, $historyCourses] as $rows) {
            foreach ($rows as $row) {
                $courseId = (int) ($row['id'] ?? 0);
                if ($courseId > 0) {
                    $ids[$courseId] = $courseId;
                }
            }
        }

        return array_values($ids);
    }

    /**
     * @param array<int,array<string,mixed>> $courses
     * @param array<int,array<string,mixed>> $courseStats
     * @return array<int,array<string,mixed>>
     */
    private function attachCourseStats(array $courses, array $courseStats): array
    {
        foreach ($courses as &$course) {
            $courseId = (int) ($course['id'] ?? 0);
            $course['stats'] = $courseStats[$courseId] ?? [
                'students' => 0,
                'revenue_xof' => 0,
                'paid_orders' => 0,
                'avg_rating' => 0.0,
                'rating_count' => 0,
                'completion_rate' => 0,
                'monthly_enrollments' => [],
            ];
        }
        unset($course);

        return $courses;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @return array<int,string>
     */
    private function handleSingleAction(array $user, array $post, string $action, string &$success): array
    {
        $courseId = (int) ($post['course_id'] ?? 0);
        if ($courseId < 1) {
            return ['Cours invalide.'];
        }

        $course = $this->model->findCourseWithTeacherById($courseId);
        if (!$course) {
            return ['Cours introuvable.'];
        }
        $currentStatus = (string) ($course['status'] ?? '');
        if ($action === 'remove') {
            if ($currentStatus !== 'approved') {
                return ["Seuls les cours approuvés peuvent être retirés depuis cette vue."];
            }
        } elseif ($currentStatus !== 'submitted') {
            return ["Ce cours n'est plus en attente de validation."];
        }

        $reason = $this->resolveReason($post);
        if (in_array($action, ['reject', 'correction', 'remove'], true) && $reason === '') {
            return ['Le motif est obligatoire pour cette action.'];
        }

        try {
            $this->model->beginTransaction();
            $this->applyModerationAction($user, $course, $action, $reason);
            $this->model->commit();
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            return ['Erreur lors du traitement : ' . $e->getMessage()];
        }

        $success = match ($action) {
            'approve' => 'Cours approuvé et notification envoyée.',
            'correction' => 'Demande de correction envoyée.',
            'remove' => 'Cours retiré et enseignant notifié.',
            default => 'Cours rejeté et notification envoyée.',
        };

        return [];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @return array<int,string>
     */
    private function handleBulkAction(array $user, array $post, string $action, string &$success): array
    {
        $courseIdsRaw = $post['course_ids'] ?? [];
        $courseIds = [];
        if (is_array($courseIdsRaw)) {
            foreach ($courseIdsRaw as $rawId) {
                $id = (int) $rawId;
                if ($id > 0) {
                    $courseIds[$id] = $id;
                }
            }
        }
        $courseIds = array_values($courseIds);
        if ($courseIds === []) {
            return ['Aucun cours sélectionné pour le traitement en lot.'];
        }

        $normalizedAction = match ($action) {
            'bulk_approve' => 'approve',
            'bulk_correction' => 'correction',
            default => 'reject',
        };

        $reason = $this->resolveReason($post);
        if (in_array($normalizedAction, ['reject', 'correction'], true) && $reason === '') {
            return ['Le motif est obligatoire pour cette action en lot.'];
        }

        $processed = 0;
        $skipped = 0;

        try {
            $this->model->beginTransaction();
            foreach ($courseIds as $courseId) {
                $course = $this->model->findCourseWithTeacherById((int) $courseId);
                if (!$course || (string) ($course['status'] ?? '') !== 'submitted') {
                    $skipped++;
                    continue;
                }

                $this->applyModerationAction($user, $course, $normalizedAction, $reason);
                $processed++;
            }
            $this->model->commit();
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            return ['Erreur lors du traitement en lot : ' . $e->getMessage()];
        }

        $success = 'Traitement en lot terminé : '
            . $processed . ' traité(s)'
            . ($skipped > 0 ? ', ' . $skipped . ' ignoré(s).' : '.');

        return [];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $course
     */
    private function applyModerationAction(array $user, array $course, string $action, string $reason): void
    {
        $courseId = (int) ($course['id'] ?? 0);
        $teacherId = (int) ($course['teacher_user_id'] ?? 0);
        $courseTitle = trim((string) ($course['title'] ?? 'Cours'));

        if ($action === 'approve') {
            $this->model->markCourseApproved($courseId);
            $this->model->insertNotification(
                $teacherId,
                'Cours approuvé',
                $courseTitle . " a été approuvé par l'administration.",
                $this->siteUrl('teacher/courses')
            );
            $this->model->insertAudit((int) ($user['id'] ?? 0), 'course_approve', $courseId, ['moderation_type' => 'approve']);
            return;
        }

        if ($action === 'remove') {
            $this->model->markCourseRejected($courseId, $reason);
            $this->model->insertNotification(
                $teacherId,
                'Cours retiré du catalogue',
                $courseTitle . " a été retiré du catalogue par l'administration. Motif : " . $reason,
                $this->siteUrl('teacher/courses?status=rejected')
            );
            $this->model->insertAudit((int) ($user['id'] ?? 0), 'course_remove', $courseId, [
                'moderation_type' => 'remove',
                'reason' => $reason,
                'previous_status' => 'approved',
                'new_status' => 'rejected',
            ]);
            return;
        }

        $auditAction = $action === 'correction' ? 'course_correction_request' : 'course_reject';
        $notificationTitle = $action === 'correction' ? 'Correction demandée' : 'Cours rejeté';
        $notificationBody = $action === 'correction'
            ? ($courseTitle . ' doit être corrigé puis soumis à nouveau. Motif : ' . $reason)
            : ($courseTitle . ' a été rejeté. Motif : ' . $reason);

        $this->model->markCourseRejected($courseId, $reason);
        $this->model->insertNotification($teacherId, $notificationTitle, $notificationBody, $this->siteUrl('teacher/courses'));
        $this->model->insertAudit((int) ($user['id'] ?? 0), $auditAction, $courseId, [
            'moderation_type' => $action,
            'reason' => $reason,
        ]);
    }

    /**
     * @param array<string,mixed> $post
     */
    private function resolveReason(array $post): string
    {
        $rawReason = trim((string) ($post['reason'] ?? ''));
        if ($rawReason !== '') {
            return $rawReason;
        }

        $preset = trim((string) ($post['reason_preset'] ?? ''));
        $custom = trim((string) ($post['reason_custom'] ?? ''));

        $reason = '';
        if ($preset !== '' && $preset !== 'other') {
            $reason = $preset;
        }
        if ($custom !== '') {
            $reason = $reason !== '' ? ($reason . ' - ' . $custom) : $custom;
        }

        return trim($reason);
    }
}
