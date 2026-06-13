<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;

use App\Models\TeacherCoursesModel;

final class CoursesService extends Service
{
    private TeacherCoursesModel $model;

    public function __construct(TeacherCoursesModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<int,string> $roles
     * @param array<string,mixed> $post
     * @param array<string,mixed> $query
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(array $user, array $roles, string $method, array $post, array $query = []): array
    {
        $flashErrors = $this->flashGet('errors', []);
        $errors = is_array($flashErrors)
            ? array_values(array_filter(array_map(
                static fn($error): string => trim((string) $error),
                $flashErrors
            )))
            : [];
        $flashError = trim((string) $this->flashGet('error', ''));
        if ($flashError !== '') {
            $errors[] = $flashError;
        }
        $success = (string) $this->flashGet('success', '');
        $postRedirectTo = null;
        $isAdmin = in_array('admin', $roles, true);
        $teacherId = (int) ($user['id'] ?? 0);

        $statusLabels = [
            'draft' => 'Brouillon',
            'submitted' => 'Soumis',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'archived' => 'Archivé',
        ];
        $levelLabels = [
            'all' => 'Tous niveaux',
            'beginner' => 'Débutant',
            'intermediate' => 'Intermédiaire',
            'advanced' => 'Avancé',
        ];
        $sortLabels = [
            'updated_desc' => 'Plus récents',
            'updated_asc' => 'MàJ ancienne',
            'created_desc' => 'Création récente',
            'created_asc' => 'Création ancienne',
            'revenue_desc' => 'Revenus',
            'students_desc' => 'Étudiants',
            'price_asc' => 'Prix croissant',
            'price_desc' => 'Prix décroissant',
            'status_asc' => 'Statut A-Z',
            'status_desc' => 'Statut Z-A',
            'title_asc' => 'Titre A-Z',
            'title_desc' => 'Titre Z-A',
        ];
        $perPageOptions = [6, 12, 24, 48];

        $listState = $this->normalizeListState($query);
        $periodState = [
            'key' => (string) ($listState['period'] ?? 'all'),
            'from_date' => (string) ($listState['from'] ?? ''),
            'to_date' => (string) ($listState['to'] ?? ''),
            'start_at' => (string) ($listState['period_start_at'] ?? ''),
            'end_at' => (string) ($listState['period_end_at'] ?? ''),
            'label' => (string) ($listState['period_label'] ?? 'Depuis le début'),
            'warning' => (string) ($listState['period_warning'] ?? ''),
        ];
        $filters = [
            'q' => $listState['q'],
            'status' => $listState['status'],
            'level' => $listState['level'],
            'price_min' => $listState['price_min'],
            'price_max' => $listState['price_max'],
            'sort' => $listState['sort'],
            'period_start_at' => $periodState['start_at'],
            'period_end_at' => $periodState['end_at'],
        ];

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');
            if ($action === 'bulk') {
                $this->handleBulkAction($post, $user, $isAdmin, $errors, $success);
            } else {
                $courseId = (int) ($post['course_id'] ?? 0);
                if ($action === 'archive' && $courseId > 0) {
                    $this->handleArchive($courseId, $user, $isAdmin, $errors, $success);
                } elseif ($action === 'delete' && $courseId > 0) {
                    // Backward compatibility: old "Retirer" action mapped to archive.
                    $this->handleArchive($courseId, $user, $isAdmin, $errors, $success);
                } elseif ($action === 'restore' && $courseId > 0) {
                    $this->handleRestore($courseId, $user, $isAdmin, $errors, $success);
                } elseif ($action === 'duplicate' && $courseId > 0) {
                    $this->handleDuplicate($courseId, $user, $isAdmin, $errors, $success);
                } elseif ($action === 'new_version' && $courseId > 0) {
                    $newCourseId = $this->handleNewVersion($courseId, $user, $isAdmin, $errors, $success);
                    if ($newCourseId > 0) {
                        $postRedirectTo = $this->siteUrl('teacher/course_edit?id=' . $newCourseId);
                    }
                } elseif ($action === 'save_draft' && $courseId > 0) {
                    $this->handleSaveDraft($courseId, $user, $isAdmin, $errors, $success);
                } elseif ($action === 'submit' && $courseId > 0) {
                    $this->handleSubmit($courseId, $user, $isAdmin, $errors, $success);
                }
            }
        }

        if ($method === 'POST' && $errors === [] && $success !== '') {
            $this->flashSet('success', $success);
            if ($postRedirectTo !== null) {
                return [
                    'redirectTo' => $postRedirectTo,
                    'view' => [],
                ];
            }
            $redirectQuery = $this->normalizeListQueryFromPost($post);
            $redirectTo = $this->siteUrl('teacher/courses');
            if ($redirectQuery !== '') {
                $redirectTo .= '?' . $redirectQuery;
            }
            return [
                'redirectTo' => $redirectTo,
                'view' => [],
            ];
        }

        $totalCourses = $this->model->countCourses($isAdmin ? null : $teacherId, $isAdmin, $filters);
        $perPage = (int) $listState['per_page'];
        $totalPages = max(1, (int) ceil(($totalCourses > 0 ? $totalCourses : 1) / $perPage));
        $page = min((int) $listState['page'], $totalPages);
        $listState['page'] = $page;
        $offset = ($page - 1) * $perPage;

        $courses = $this->model->listCourses(
            $isAdmin ? null : $teacherId,
            $isAdmin,
            $filters,
            $offset,
            $perPage
        );

        $courseIds = [];
        foreach ($courses as $course) {
            $courseIds[] = (int) ($course['id'] ?? 0);
        }

        $submissionCountsMap = $this->model->findSubmissionCountsByCourseIds($courseIds);
        $statsMap = $this->model->findCourseStatsByCourseIds($courseIds);

        foreach ($courses as &$course) {
            $courseId = (int) ($course['id'] ?? 0);
            $submission = $submissionCountsMap[$courseId] ?? [
                'chapters_cnt' => 0,
                'lessons_cnt' => 0,
                'meetings_cnt' => 0,
                'exams_cnt' => 0,
                'adv_exams_cnt' => 0,
            ];
            $course['submission'] = $submission;
            $course['submission_ready'] = ((int) ($submission['chapters_cnt'] ?? 0) > 0)
                && ((int) ($submission['lessons_cnt'] ?? 0) > 0)
                && ((int) ($submission['meetings_cnt'] ?? 0) > 0);

            $course['stats'] = $statsMap[$courseId] ?? [
                'views' => 0,
                'enrollments' => 0,
                'paid_sales' => 0,
                'paid_amount_xof' => 0,
            ];

            if (empty($course['workflow_status'])) {
                $course['workflow_status'] = !empty($course['deleted_at'])
                    ? 'archived'
                    : (string) ($course['status'] ?? 'draft');
            }
        }
        unset($course);

        $counterFilters = [
            'q' => $listState['q'],
            'level' => $listState['level'],
            'price_min' => $listState['price_min'],
            'price_max' => $listState['price_max'],
            'period_start_at' => $periodState['start_at'],
            'period_end_at' => $periodState['end_at'],
        ];
        $statusCounters = $this->model->countCoursesByWorkflowStatus(
            $isAdmin ? null : $teacherId,
            $isAdmin,
            $counterFilters
        );
        $statusCounters['all'] = (int) array_sum($statusCounters);
        $portfolioSummary = $this->model->summarizePortfolio($isAdmin ? null : $teacherId, $isAdmin);
        $recentDraftCourses = $this->model->listCourses(
            $isAdmin ? null : $teacherId,
            $isAdmin,
            [
                'status' => 'draft',
                'sort' => 'updated_desc',
                'level' => 'all',
                'q' => '',
                'price_min' => null,
                'price_max' => null,
            ],
            0,
            4
        );
        $recentRejectedCourses = $this->model->listCourses(
            $isAdmin ? null : $teacherId,
            $isAdmin,
            [
                'status' => 'rejected',
                'sort' => 'updated_desc',
                'level' => 'all',
                'q' => '',
                'price_min' => null,
                'price_max' => null,
            ],
            0,
            2
        );

        $resumeCourses = [];
        $resumeIds = [];
        foreach (array_merge($recentDraftCourses, $recentRejectedCourses) as $draftCourse) {
            $courseId = (int) ($draftCourse['id'] ?? 0);
            if ($courseId < 1 || isset($resumeIds[$courseId])) {
                continue;
            }
            $resumeIds[$courseId] = true;
            $resumeCourses[] = $draftCourse;
        }

        if ($resumeCourses !== []) {
            $resumeIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $resumeCourses);
            $resumeSubmissionMap = $this->model->findSubmissionCountsByCourseIds($resumeIds);
            $resumeStatsMap = $this->model->findCourseStatsByCourseIds($resumeIds);
            foreach ($resumeCourses as &$resumeCourse) {
                $courseId = (int) ($resumeCourse['id'] ?? 0);
                $resumeCourse['submission'] = $resumeSubmissionMap[$courseId] ?? [
                    'chapters_cnt' => 0,
                    'lessons_cnt' => 0,
                    'meetings_cnt' => 0,
                    'exams_cnt' => 0,
                    'adv_exams_cnt' => 0,
                ];
                $resumeCourse['submission_ready'] = ((int) ($resumeCourse['submission']['chapters_cnt'] ?? 0) > 0)
                    && ((int) ($resumeCourse['submission']['lessons_cnt'] ?? 0) > 0)
                    && ((int) ($resumeCourse['submission']['meetings_cnt'] ?? 0) > 0);
                $resumeCourse['stats'] = $resumeStatsMap[$courseId] ?? [
                    'views' => 0,
                    'enrollments' => 0,
                    'paid_sales' => 0,
                    'paid_amount_xof' => 0,
                ];
            }
            unset($resumeCourse);
        }

        $listQuery = $this->buildListQuery($listState);

        return [
            'redirectTo' => null,
            'view' => [
                'user' => $user,
                'roles' => $roles,
                'errors' => $errors,
                'success' => $success,
                'statusLabels' => $statusLabels,
                'levelLabels' => $levelLabels,
                'sortLabels' => $sortLabels,
                'perPageOptions' => $perPageOptions,
                'filters' => $listState,
                'periodState' => $periodState,
                'statusCounters' => $statusCounters,
                'portfolioSummary' => $portfolioSummary,
                'resumeCourses' => $resumeCourses,
                'listQuery' => $listQuery,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCourses,
                    'total_pages' => $totalPages,
                    'has_prev' => $page > 1,
                    'has_next' => $page < $totalPages,
                    'start' => $totalCourses > 0 ? ($offset + 1) : 0,
                    'end' => $totalCourses > 0 ? ($offset + count($courses)) : 0,
                ],
                'courses' => $courses,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<int,string> $errors
     */
    private function handleArchive(
        int $courseId,
        array $user,
        bool $isAdmin,
        array &$errors,
        string &$success
    ): void {
        $course = $this->loadOwnedCourse($courseId, $user, $isAdmin, $errors);
        if (!$course) {
            return;
        }
        if (!empty($course['deleted_at'])) {
            $errors[] = 'Ce cours est déjà archivé.';
            return;
        }

        try {
            $this->model->beginTransaction();
            $this->model->archiveCourse($courseId);
            $this->model->insertAudit(
                (int) ($user['id'] ?? 0),
                'course_archive',
                'course',
                $courseId
            );
            $this->model->commit();
            $success = 'Cours archivé.';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $errors[] = 'Erreur lors de l’archivage du cours.';
        }
    }

    /**
     * @param array<string,mixed> $user
     * @param array<int,string> $errors
     */
    private function handleRestore(
        int $courseId,
        array $user,
        bool $isAdmin,
        array &$errors,
        string &$success
    ): void {
        $course = $this->loadOwnedCourse($courseId, $user, $isAdmin, $errors, true);
        if (!$course) {
            return;
        }
        if (empty($course['deleted_at'])) {
            $errors[] = 'Ce cours n’est pas archivé.';
            return;
        }

        try {
            $this->model->beginTransaction();
            $this->model->restoreCourse($courseId);
            $this->model->insertAudit((int) ($user['id'] ?? 0), 'course_restore', 'course', $courseId);
            $this->model->commit();
            $success = 'Cours restauré.';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $errors[] = 'Erreur lors de la restauration du cours.';
        }
    }

    /**
     * @param array<string,mixed> $user
     * @param array<int,string> $errors
     */
    private function handleNewVersion(
        int $courseId,
        array $user,
        bool $isAdmin,
        array &$errors,
        string &$success
    ): int {
        $course = $this->loadOwnedCourse($courseId, $user, $isAdmin, $errors);
        if (!$course) {
            return 0;
        }
        if ((string) ($course['status'] ?? '') !== 'approved') {
            $errors[] = 'Nouvelle version uniquement depuis un cours approuvé.';
            return 0;
        }

        try {
            $this->model->beginTransaction();
            $newCourseId = $this->duplicateCourseTree($courseId, $course);
            $this->model->insertAudit((int) ($user['id'] ?? 0), 'course_new_version', 'course', $newCourseId);
            $this->model->commit();
            $success = 'Nouvelle version créée en brouillon. Vous pouvez la modifier maintenant.';
            return $newCourseId;
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $errors[] = 'Erreur lors de la création de la nouvelle version.';
            return 0;
        }
    }

    /**
     * @param array<string,mixed> $user
     * @param array<int,string> $errors
     */
    private function handleDuplicate(
        int $courseId,
        array $user,
        bool $isAdmin,
        array &$errors,
        string &$success
    ): void {
        $course = $this->loadOwnedCourse($courseId, $user, $isAdmin, $errors, true);
        if (!$course) {
            return;
        }

        try {
            $this->model->beginTransaction();
            $newCourseId = $this->duplicateCourseTree($courseId, $course);
            $this->model->insertAudit((int) ($user['id'] ?? 0), 'course_duplicate', 'course', $newCourseId);
            $this->model->commit();
            $success = 'Copie du cours créée en brouillon.';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $errors[] = 'Erreur lors de la duplication du cours.';
        }
    }

    /**
     * @param array<string,mixed> $user
     * @param array<int,string> $errors
     */
    private function handleSaveDraft(
        int $courseId,
        array $user,
        bool $isAdmin,
        array &$errors,
        string &$success
    ): void {
        $course = $this->loadOwnedCourse($courseId, $user, $isAdmin, $errors);
        if (!$course) {
            return;
        }
        if (!empty($course['deleted_at'])) {
            $errors[] = 'Cours archivé : opération impossible.';
            return;
        }
        if (in_array((string) ($course['status'] ?? ''), ['submitted', 'approved'], true)) {
            $errors[] = 'Ce cours est gelé et ne peut plus repasser en brouillon.';
            return;
        }

        try {
            $this->model->beginTransaction();
            $this->model->markCourseAsDraft($courseId);
            $this->model->insertAudit((int) ($user['id'] ?? 0), 'course_save_draft', 'course', $courseId);
            $this->model->commit();
            $success = 'Cours enregistré en brouillon.';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $errors[] = 'Erreur lors de l’enregistrement en brouillon.';
        }
    }

    /**
     * @param array<string,mixed> $user
     * @param array<int,string> $errors
     */
    private function handleSubmit(
        int $courseId,
        array $user,
        bool $isAdmin,
        array &$errors,
        string &$success
    ): void {
        $course = $this->loadOwnedCourse($courseId, $user, $isAdmin, $errors);
        if (!$course) {
            return;
        }
        if (!empty($course['deleted_at'])) {
            $errors[] = 'Cours archivé : soumission impossible.';
            return;
        }
        if (!in_array((string) ($course['status'] ?? ''), ['draft', 'rejected'], true)) {
            $errors[] = 'Ce cours n’est pas modifiable.';
            return;
        }

        $counts = $this->model->findSubmissionCounts($courseId);
        if (($counts['chapters_cnt'] ?? 0) < 1 || ($counts['lessons_cnt'] ?? 0) < 1) {
            $errors[] = 'Ajoutez au moins un chapitre et une leçon avant de soumettre.';
        }
        if (($counts['meetings_cnt'] ?? 0) < 1) {
            $errors[] = 'Ajoutez au moins un rendez-vous avant de soumettre.';
        }
        if ($errors !== []) {
            return;
        }

        try {
            $this->model->beginTransaction();
            $this->model->markCourseAsSubmitted($courseId);
            $this->model->insertAudit((int) ($user['id'] ?? 0), 'course_submit', 'course', $courseId);

            foreach ($this->model->findAdminIds() as $adminId) {
                $this->model->insertNotification(
                    $adminId,
                    'Cours soumis',
                    ((string) ($course['title'] ?? 'Cours')) . ' a été soumis pour validation.',
                    $this->siteUrl('admin/courses?course_id=' . $courseId)
                );
            }

            $this->model->commit();
            $success = 'Cours soumis, notification envoyée à l’admin.';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $errors[] = 'Erreur lors de la soumission.';
        }
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $user
     * @param array<int,string> $errors
     */
    private function handleBulkAction(
        array $post,
        array $user,
        bool $isAdmin,
        array &$errors,
        string &$success
    ): void {
        $bulkAction = trim((string) ($post['bulk_action'] ?? ''));
        if (!in_array($bulkAction, ['submit', 'archive', 'restore', 'duplicate'], true)) {
            $errors[] = 'Action groupée invalide.';
            return;
        }

        $selectedRaw = $post['selected_ids'] ?? [];
        $selected = [];
        if (is_array($selectedRaw)) {
            foreach ($selectedRaw as $value) {
                $id = (int) $value;
                if ($id > 0) {
                    $selected[$id] = $id;
                }
            }
        }
        $courseIds = array_values($selected);
        if ($courseIds === []) {
            $errors[] = 'Selection vide.';
            return;
        }

        $okCount = 0;
        $failCount = 0;
        $failSamples = [];

        foreach ($courseIds as $courseId) {
            $localErrors = [];
            $localSuccess = '';

            if ($bulkAction === 'submit') {
                $this->handleSubmit($courseId, $user, $isAdmin, $localErrors, $localSuccess);
            } elseif ($bulkAction === 'archive') {
                $this->handleArchive($courseId, $user, $isAdmin, $localErrors, $localSuccess);
            } elseif ($bulkAction === 'restore') {
                $this->handleRestore($courseId, $user, $isAdmin, $localErrors, $localSuccess);
            } elseif ($bulkAction === 'duplicate') {
                $this->handleDuplicate($courseId, $user, $isAdmin, $localErrors, $localSuccess);
            }

            if ($localErrors === []) {
                $okCount++;
            } else {
                $failCount++;
                if (count($failSamples) < 3) {
                    $failSamples[] = 'Cours #' . $courseId . ': ' . (string) $localErrors[0];
                }
            }
        }

        if ($okCount > 0) {
            $success = $okCount . ' cours traites avec succes.';
        }
        if ($failCount > 0) {
            $errors[] = $failCount . ' cours non traites.';
            foreach ($failSamples as $sample) {
                $errors[] = $sample;
            }
        }
    }

    /**
     * @param array<string,mixed> $user
     * @param array<int,string> $errors
     * @return array<string,mixed>|null
     */
    private function loadOwnedCourse(
        int $courseId,
        array $user,
        bool $isAdmin,
        array &$errors,
        bool $includeArchived = false
    ): ?array {
        $course = $includeArchived
            ? $this->model->findCourseByIdIncludingArchived($courseId)
            : $this->model->findCourseById($courseId);
        if (!$course) {
            $errors[] = 'Cours introuvable.';
            return null;
        }
        if (!$isAdmin && (int) ($course['teacher_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            $errors[] = 'Accès refusé.';
            return null;
        }
        return $course;
    }

    /**
     * @param array<string,mixed> $course
     */
    private function duplicateCourseTree(int $sourceCourseId, array $course): int
    {
        $baseVersionOf = (int) ($course['version_of'] ?? 0);
        if ($baseVersionOf < 1) {
            $baseVersionOf = $sourceCourseId;
        }

        $slugSuffix = '-copy-' . date('YmdHis') . '-' . bin2hex(random_bytes(2));
        $baseSlug = (string) ($course['slug'] ?? ('cours-' . $sourceCourseId));
        $maxBaseLength = 220 - strlen($slugSuffix);
        if ($maxBaseLength < 10) {
            $maxBaseLength = 10;
        }
        $baseSlug = substr($baseSlug, 0, $maxBaseLength);
        $newSlug = rtrim($baseSlug, '-') . $slugSuffix;
        $newCourseId = $this->model->insertCourseVersion($course, $newSlug, $baseVersionOf);

        $chapterMap = [];
        foreach ($this->model->findChaptersByCourseId($sourceCourseId) as $chapter) {
            $oldChapterId = (int) ($chapter['id'] ?? 0);
            if ($oldChapterId < 1) {
                continue;
            }
            $chapterMap[$oldChapterId] = $this->model->insertChapter(
                $newCourseId,
                (string) ($chapter['title'] ?? ''),
                isset($chapter['description']) ? (string) $chapter['description'] : null,
                (int) ($chapter['position'] ?? 1)
            );
        }

        if ($chapterMap !== []) {
            $oldChapterIds = array_keys($chapterMap);
            foreach ($this->model->findLessonsByChapterIds($oldChapterIds) as $lesson) {
                $oldChapterId = (int) ($lesson['chapter_id'] ?? 0);
                $newChapterId = (int) ($chapterMap[$oldChapterId] ?? 0);
                if ($newChapterId < 1) {
                    continue;
                }
                $this->model->insertLesson($newChapterId, $lesson);
            }

            foreach ($this->model->findChapterDocumentsByChapterIds($oldChapterIds) as $doc) {
                $oldChapterId = (int) ($doc['chapter_id'] ?? 0);
                $newChapterId = (int) ($chapterMap[$oldChapterId] ?? 0);
                if ($newChapterId < 1) {
                    continue;
                }
                $this->model->insertChapterDocument($newChapterId, $doc);
            }
        }

        return $newCourseId;
    }

    /**
     * @param array<string,mixed> $input
     * @return array{
     *   q:string,
     *   status:string,
     *   level:string,
     *   price_min:?int,
     *   price_max:?int,
     *   sort:string,
     *   per_page:int,
     *   page:int,
     *   period:string,
     *   from:string,
     *   to:string,
     *   period_start_at:string,
     *   period_end_at:string,
     *   period_label:string,
     *   period_warning:string
     * }
     */
    private function normalizeListState(array $input): array
    {
        $allowedStatuses = ['all', 'draft', 'submitted', 'approved', 'rejected', 'archived'];
        $allowedLevels = ['all', 'beginner', 'intermediate', 'advanced'];
        $allowedSorts = [
            'updated_desc', 'updated_asc', 'created_desc', 'created_asc',
            'revenue_desc', 'students_desc',
            'price_asc', 'price_desc', 'status_asc', 'status_desc',
            'title_asc', 'title_desc',
        ];
        $allowedPerPage = [6, 12, 24, 48];

        $q = trim((string) ($input['q'] ?? ''));
        if (strlen($q) > 120) {
            $q = substr($q, 0, 120);
        }

        $status = (string) ($input['status'] ?? 'all');
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $level = (string) ($input['level'] ?? 'all');
        if (!in_array($level, $allowedLevels, true)) {
            $level = 'all';
        }

        $sort = (string) ($input['sort'] ?? 'updated_desc');
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'updated_desc';
        }

        $perPage = (int) ($input['per_page'] ?? 12);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 12;
        }

        $page = max(1, (int) ($input['page'] ?? 1));

        $priceMin = $this->normalizeNullableInt($input['price_min'] ?? null);
        $priceMax = $this->normalizeNullableInt($input['price_max'] ?? null);
        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            [$priceMin, $priceMax] = [$priceMax, $priceMin];
        }

        $periodState = $this->resolvePeriodState($input);

        return [
            'q' => $q,
            'status' => $status,
            'level' => $level,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'sort' => $sort,
            'per_page' => $perPage,
            'page' => $page,
            'period' => (string) ($periodState['key'] ?? 'all'),
            'from' => (string) ($periodState['from_date'] ?? ''),
            'to' => (string) ($periodState['to_date'] ?? ''),
            'period_start_at' => (string) ($periodState['start_at'] ?? ''),
            'period_end_at' => (string) ($periodState['end_at'] ?? ''),
            'period_label' => (string) ($periodState['label'] ?? 'Depuis le début'),
            'period_warning' => (string) ($periodState['warning'] ?? ''),
        ];
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }
        $int = (int) $raw;
        return $int >= 0 ? $int : null;
    }

    /**
     * @param array{
     *   q:string,
     *   status:string,
     *   level:string,
     *   price_min:?int,
     *   price_max:?int,
     *   sort:string,
     *   per_page:int,
     *   page:int,
     *   period:string,
     *   from:string,
     *   to:string
     * } $state
     */
    private function buildListQuery(array $state): string
    {
        $params = [];
        if ($state['q'] !== '') {
            $params['q'] = $state['q'];
        }
        if ($state['status'] !== 'all') {
            $params['status'] = $state['status'];
        }
        if ($state['level'] !== 'all') {
            $params['level'] = $state['level'];
        }
        if ($state['price_min'] !== null) {
            $params['price_min'] = (string) $state['price_min'];
        }
        if ($state['price_max'] !== null) {
            $params['price_max'] = (string) $state['price_max'];
        }
        if ($state['sort'] !== 'updated_desc') {
            $params['sort'] = $state['sort'];
        }
        if ($state['per_page'] !== 12) {
            $params['per_page'] = (string) $state['per_page'];
        }
        if ($state['page'] > 1) {
            $params['page'] = (string) $state['page'];
        }
        if (($state['period'] ?? 'all') !== 'all') {
            $params['period'] = (string) $state['period'];
            if (($state['period'] ?? '') === 'custom') {
                $params['from'] = (string) ($state['from'] ?? '');
                $params['to'] = (string) ($state['to'] ?? '');
            }
        }

        return http_build_query($params);
    }

    /**
     * @param array<string,mixed> $post
     */
    private function normalizeListQueryFromPost(array $post): string
    {
        $raw = trim((string) ($post['list_query'] ?? ''));
        if ($raw === '') {
            return '';
        }
        parse_str($raw, $parsed);
        if (!is_array($parsed)) {
            return '';
        }
        $normalized = $this->normalizeListState($parsed);
        return $this->buildListQuery($normalized);
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    private function resolvePeriodState(array $query): array
    {
        $key = strtolower(trim((string) ($query['period'] ?? 'all')));
        $key = match ($key) {
            '0' => 'all',
            '7j' => '7d',
            '30j' => '30d',
            '90j' => '90d',
            default => $key,
        };
        if (!in_array($key, ['all', '7d', '30d', '90d', 'custom'], true)) {
            $key = 'all';
        }

        $warning = '';
        $today = new \DateTimeImmutable('today');
        $from = null;
        $to = null;
        $label = 'Depuis le début';

        if ($key === '7d') {
            $from = $today->modify('-6 days');
            $to = $today;
            $label = '7 derniers jours';
        } elseif ($key === '90d') {
            $from = $today->modify('-89 days');
            $to = $today;
            $label = '90 derniers jours';
        } elseif ($key === 'custom') {
            $rawFrom = trim((string) ($query['from'] ?? ''));
            $rawTo = trim((string) ($query['to'] ?? ''));
            $fromTs = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) === 1) ? strtotime($rawFrom . ' 00:00:00') : false;
            $toTs = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo) === 1) ? strtotime($rawTo . ' 00:00:00') : false;
            if ($fromTs === false || $toTs === false || $fromTs > $toTs) {
                $warning = '';
                $key = 'all';
            } else {
                $from = (new \DateTimeImmutable())->setTimestamp($fromTs);
                $to = (new \DateTimeImmutable())->setTimestamp($toTs);
                $label = 'Période personnalisée';
            }
        }

        if ($key === '30d') {
            $from = $today->modify('-29 days');
            $to = $today;
            $label = '30 derniers jours';
        }

        return [
            'key' => $key,
            'from_date' => $from ? $from->format('Y-m-d') : '',
            'to_date' => $to ? $to->format('Y-m-d') : '',
            'start_at' => $from ? $from->format('Y-m-d 00:00:00') : '',
            'end_at' => $to ? $to->modify('+1 day')->format('Y-m-d 00:00:00') : '',
            'label' => $label,
            'warning' => $warning,
        ];
    }
}
