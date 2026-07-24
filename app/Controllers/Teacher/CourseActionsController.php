<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\TeacherCoursesModel;

final class CourseActionsController extends Controller
{
    public function __invoke(): void
    {
        $siteLogoUrl = $this->siteLogoUrl();
        $courseId = (int) ($_GET['id'] ?? 0);
        $returnPath = $this->siteUrl('teacher/course_actions' . ($courseId > 0 ? ('?id=' . $courseId) : ''));

        $user = $this->requireAuth(true, $returnPath);
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        if (!$isAdmin && !in_array('teacher', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ($courseId < 1) {
            header('Location: ' . $this->siteUrl('teacher/courses'));
            exit;
        }

        /** @var \PDO $pdo */
        $pdo = $this->pdo();
        $model = new TeacherCoursesModel($pdo);
        $course = $model->findCourseByIdIncludingArchived($courseId);
        if (!$course) {
            header('Location: ' . $this->siteUrl('teacher/courses'));
            exit;
        }
        if (!$isAdmin && (int) ($course['teacher_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            header('Location: ' . $this->siteUrl('teacher/courses'));
            exit;
        }

        $submission = $model->findSubmissionCounts($courseId);
        $statsMap = $model->findCourseStatsByCourseIds([$courseId]);
        $stats = $statsMap[$courseId] ?? [
            'views' => 0,
            'enrollments' => 0,
            'paid_sales' => 0,
            'paid_amount_xof' => 0,
        ];

        $workflow = !empty($course['deleted_at'])
            ? 'archived'
            : (string) ($course['status'] ?? 'draft');
        $course['workflow_status'] = $workflow;
        $course['submission'] = $submission;
        $course['submission_ready'] = ((int) ($submission['chapters_cnt'] ?? 0) > 0)
            && ((int) ($submission['lessons_cnt'] ?? 0) > 0)
            && ((int) ($submission['meetings_cnt'] ?? 0) > 0);
        $course['stats'] = $stats;

        $listQuery = $this->sanitizeListQuery((string) ($_GET['list'] ?? ''));
        $backUrl = $this->siteUrl('teacher/courses' . ($listQuery !== '' ? ('?' . $listQuery) : ''));

        $this->render('teacher/course_actions', [
            'siteLogoUrl' => $siteLogoUrl,
            'user' => $user,
            'roles' => array_values($roles),
            'course' => $course,
            'listQuery' => $listQuery,
            'backUrl' => $backUrl,
        ]);
    }

    private function sanitizeListQuery(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        parse_str($raw, $parsed);
        if (!is_array($parsed)) {
            return '';
        }

        $status = (string) ($parsed['status'] ?? 'all');
        if (!in_array($status, ['all', 'draft', 'submitted', 'approved', 'rejected', 'archived'], true)) {
            $status = 'all';
        }
        $level = (string) ($parsed['level'] ?? 'all');
        if (!in_array($level, ['all', 'beginner', 'intermediate', 'advanced'], true)) {
            $level = 'all';
        }
        $sort = (string) ($parsed['sort'] ?? 'updated_desc');
        if (!in_array($sort, [
            'updated_desc',
            'updated_asc',
            'created_desc',
            'created_asc',
            'price_asc',
            'price_desc',
            'status_asc',
            'status_desc',
            'title_asc',
            'title_desc',
        ], true)) {
            $sort = 'updated_desc';
        }
        $perPage = (int) ($parsed['per_page'] ?? 12);
        if (!in_array($perPage, [6, 12, 24, 48], true)) {
            $perPage = 12;
        }
        $page = max(1, (int) ($parsed['page'] ?? 1));
        $period = (string) ($parsed['period'] ?? 'all');
        if (!in_array($period, ['all', '7d', '30d', '90d', 'custom'], true)) {
            $period = 'all';
        }

        $priceMinRaw = trim((string) ($parsed['price_min'] ?? ''));
        $priceMaxRaw = trim((string) ($parsed['price_max'] ?? ''));
        $q = trim((string) ($parsed['q'] ?? ''));
        if (strlen($q) > 120) {
            $q = substr($q, 0, 120);
        }

        $params = [];
        if ($q !== '') {
            $params['q'] = $q;
        }
        if ($status !== 'all') {
            $params['status'] = $status;
        }
        if ($level !== 'all') {
            $params['level'] = $level;
        }
        if ($priceMinRaw !== '' && ctype_digit($priceMinRaw)) {
            $params['price_min'] = $priceMinRaw;
        }
        if ($priceMaxRaw !== '' && ctype_digit($priceMaxRaw)) {
            $params['price_max'] = $priceMaxRaw;
        }
        if ($sort !== 'updated_desc') {
            $params['sort'] = $sort;
        }
        if ($perPage !== 12) {
            $params['per_page'] = (string) $perPage;
        }
        if ($page > 1) {
            $params['page'] = (string) $page;
        }
        if ($period !== 'all') {
            $params['period'] = $period;
            if ($period === 'custom') {
                $from = trim((string) ($parsed['from'] ?? ''));
                $to = trim((string) ($parsed['to'] ?? ''));
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) === 1) {
                    $params['from'] = $from;
                }
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) === 1) {
                    $params['to'] = $to;
                }
            }
        }

        return http_build_query($params);
    }
}

