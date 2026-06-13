<?php

declare(strict_types=1);



namespace App\Services\Admin;



use App\Core\Service;



use App\Models\AdminTeacherApplicationsModel;



final class TeacherApplicationsService extends Service

{

    private \PDO $pdo;

    private AdminTeacherApplicationsModel $model;



    public function __construct(\PDO $pdo, AdminTeacherApplicationsModel $model)

    {

        $this->pdo = $pdo;

        $this->model = $model;

    }



    /**

     * @param array<string,mixed> $adminUser

     * @param array<string,mixed> $post

     * @return array{

     *   redirectTo:?string,

     *   view:array<string,mixed>

     * }

     */

    public function handle(array $adminUser, string $method, array $post, array $query = []): array

    {

        $errors = [];

        $success = (string) $this->flashGet('success', '');
        $currentTab = $this->normalizeTab((string) ($query['tab'] ?? 'applications'));
        $searchQuery = $this->normalizeSearch((string) ($query['q'] ?? ''));



        if ($method === 'POST') {

            $action = (string) ($post['action'] ?? '');
            $redirectTab = in_array($action, ['suspend_teacher', 'reactivate_teacher'], true) ? 'teachers' : 'applications';

            if (in_array($action, ['approve', 'reject'], true)) {
                $applicationId = (int) ($post['app_id'] ?? 0);

                $reason = trim((string) ($post['reason'] ?? ''));



                $app = $this->model->findApplicationWithUserRoles($applicationId);

                if (!$app || (string) ($app['status'] ?? '') !== 'pending') {

                    $errors[] = 'Demande introuvable ou déjà traitée.';

                } else {

                    try {

                        $this->pdo->beginTransaction();



                        if ($action === 'approve') {

                            $roles = json_decode((string) ($app['roles_json'] ?? '[]'), true) ?: [];
                            if (!is_array($roles)) {
                                $roles = [];
                            }
                            if (in_array('company_admin', $roles, true) || in_array('company_member', $roles, true)) {
                                $errors[] = 'Impossible d’approuver cette demande : les comptes entreprise ne peuvent pas devenir enseignants.';
                            } else {
                                $this->model->approveApplication($applicationId, (int) ($adminUser['id'] ?? 0));



                                $roles[] = 'student';
                                $roles[] = 'teacher';
                                $roles = array_values(array_unique(array_filter($roles, static function ($role): bool {
                                    return is_string($role) && trim($role) !== '' && trim($role) !== 'teacher_candidate';
                                })));
                                $this->model->updateUserRoles((int) ($app['user_id'] ?? 0), (string) json_encode($roles));



                                $this->model->insertNotification(

                                    (int) ($app['user_id'] ?? 0),

                                    'Demande enseignant approuvée',

                                    'Votre demande a été approuvée. Vous pouvez accéder au tableau de bord enseignant.',

                                    $this->siteUrl('teacher/dashboard')

                                );

                                $this->model->insertAuditLog(
                                    (int) ($adminUser['id'] ?? 0),
                                    'teacher_application_approve',
                                    'teacher_application',
                                    $applicationId,
                                    ['user_id' => (int) ($app['user_id'] ?? 0)]
                                );

                                $success = 'Demande #' . $applicationId . ' approuvée.';
                            }

                        } elseif ($action === 'reject') {

                            if ($reason === '') {

                                $errors[] = 'Un motif est requis pour un rejet.';

                            } else {

                                $this->model->rejectApplication($applicationId, $reason, (int) ($adminUser['id'] ?? 0));

                                $this->model->insertNotification(

                                    (int) ($app['user_id'] ?? 0),

                                    'Demande enseignant rejetée',

                                    'Votre demande a été rejetée. Motif : ' . $reason,

                                    $this->siteUrl('teacher/apply')

                                );

                                $this->model->insertAuditLog(
                                    (int) ($adminUser['id'] ?? 0),
                                    'teacher_application_reject',
                                    'teacher_application',
                                    $applicationId,
                                    [
                                        'user_id' => (int) ($app['user_id'] ?? 0),
                                        'reason' => $reason,
                                    ]
                                );

                                $success = 'Demande #' . $applicationId . ' rejetée.';

                            }

                        }



                        if ($errors === []) {

                            $this->pdo->commit();

                        } else {

                            if ($this->pdo->inTransaction()) {

                                $this->pdo->rollBack();

                            }

                        }

                    } catch (\Throwable $e) {

                        if ($this->pdo->inTransaction()) {

                            $this->pdo->rollBack();

                        }

                        $errors[] = 'Erreur lors du traitement.';

                    }

                }
            } elseif (in_array($action, ['suspend_teacher', 'reactivate_teacher'], true)) {
                $teacherId = (int) ($post['teacher_id'] ?? 0);
                $teacher = $this->model->findTeacherUserById($teacherId);
                if (!$teacher) {
                    $errors[] = 'Enseignant introuvable.';
                } elseif ($teacherId === (int) ($adminUser['id'] ?? 0)) {
                    $errors[] = 'Vous ne pouvez pas suspendre votre propre compte.';
                } else {
                    try {
                        $this->pdo->beginTransaction();
                        $targetStatus = $action === 'suspend_teacher' ? 'suspended' : 'active';
                        $this->model->updateUserStatus($teacherId, $targetStatus);

                        $teacherName = trim((string) ($teacher['first_name'] ?? '') . ' ' . (string) ($teacher['last_name'] ?? ''));
                        if ($teacherName === '') {
                            $teacherName = 'Enseignant #' . $teacherId;
                        }

                        if ($targetStatus === 'suspended') {
                            $this->model->insertNotification(
                                $teacherId,
                                'Compte enseignant suspendu',
                                'Votre compte enseignant a été suspendu par l’administration. Contactez le support pour plus d’informations.',
                                $this->siteUrl('support')
                            );
                            $success = $teacherName . ' a été suspendu.';
                        } else {
                            $this->model->insertNotification(
                                $teacherId,
                                'Compte enseignant réactivé',
                                'Votre accès enseignant est de nouveau actif.',
                                $this->siteUrl('teacher/dashboard')
                            );
                            $success = $teacherName . ' a été réactivé.';
                        }

                        $this->model->insertAuditLog(
                            (int) ($adminUser['id'] ?? 0),
                            $targetStatus === 'suspended' ? 'teacher_suspend' : 'teacher_reactivate',
                            'user',
                            $teacherId,
                            ['previous_status' => (string) ($teacher['status'] ?? '')]
                        );

                        $this->pdo->commit();
                    } catch (\Throwable $e) {
                        if ($this->pdo->inTransaction()) {
                            $this->pdo->rollBack();
                        }
                        $errors[] = 'Erreur lors de la mise à jour de l’enseignant.';
                    }
                }
            } else {
                $errors[] = 'Action invalide.';
            }

        }



        if ($method === 'POST' && $errors === [] && $success !== '') {

            $this->flashSet('success', $success);

            return [

                'redirectTo' => $this->siteUrl('admin/teacher_applications?tab=' . $redirectTab),

                'view' => [],

            ];

        }

        $applications = $this->model->fetchRecentApplications(80);
        $teachers = $this->model->fetchTeacherDirectory($searchQuery, 120);
        $teacherIds = [];
        foreach ($teachers as $teacher) {
            $teacherId = (int) ($teacher['id'] ?? 0);
            if ($teacherId > 0) {
                $teacherIds[] = $teacherId;
            }
        }
        $teacherCoursesByTeacher = $this->groupCoursesByTeacher($this->model->fetchCoursesForTeacherIds($teacherIds));
        $summary = $this->buildTeacherSummary($teachers, $applications);



        return [

            'redirectTo' => null,

            'view' => [

                'errors' => $errors,

                'success' => $success,

                'applications' => $applications,

                'teacherRows' => $teachers,

                'teacherCoursesByTeacher' => $teacherCoursesByTeacher,

                'teacherSummary' => $summary,

                'currentTab' => $currentTab,

                'searchQuery' => $searchQuery,

            ],

        ];

    }

    private function normalizeTab(string $tab): string
    {
        $tab = strtolower(trim($tab));
        return in_array($tab, ['applications', 'teachers'], true) ? $tab : 'applications';
    }

    private function normalizeSearch(string $search): string
    {
        $search = trim((string) preg_replace('/\s+/', ' ', $search));
        $length = function_exists('mb_strlen') ? mb_strlen($search) : strlen($search);
        if ($length <= 120) {
            return $search;
        }

        return function_exists('mb_substr') ? mb_substr($search, 0, 120) : substr($search, 0, 120);
    }

    /**
     * @param array<int,array<string,mixed>> $courses
     * @return array<int,array<int,array<string,mixed>>>
     */
    private function groupCoursesByTeacher(array $courses): array
    {
        $grouped = [];
        foreach ($courses as $course) {
            $teacherId = (int) ($course['teacher_id'] ?? 0);
            if ($teacherId <= 0) {
                continue;
            }
            $grouped[$teacherId][] = $course;
        }
        return $grouped;
    }

    /**
     * @param array<int,array<string,mixed>> $teachers
     * @param array<int,array<string,mixed>> $applications
     * @return array<string,int|float>
     */
    private function buildTeacherSummary(array $teachers, array $applications): array
    {
        $summary = [
            'pending_applications' => 0,
            'active_teachers' => 0,
            'suspended_teachers' => 0,
            'total_courses' => 0,
            'approved_courses' => 0,
            'enrolled_students' => 0,
            'revenue_xof' => 0,
            'rating_count' => 0,
            'avg_rating' => 0.0,
        ];

        foreach ($applications as $application) {
            if ((string) ($application['status'] ?? '') === 'pending') {
                $summary['pending_applications']++;
            }
        }

        $ratingWeightedTotal = 0.0;
        foreach ($teachers as $teacher) {
            $status = strtolower(trim((string) ($teacher['status'] ?? '')));
            if ($status === 'suspended') {
                $summary['suspended_teachers']++;
            } else {
                $summary['active_teachers']++;
            }

            $summary['total_courses'] += max(0, (int) ($teacher['total_courses'] ?? 0));
            $summary['approved_courses'] += max(0, (int) ($teacher['approved_courses'] ?? 0));
            $summary['enrolled_students'] += max(0, (int) ($teacher['enrolled_students'] ?? 0));
            $summary['revenue_xof'] += max(0, (int) ($teacher['revenue_xof'] ?? 0));

            $ratingCount = max(0, (int) ($teacher['rating_count'] ?? 0));
            $avgRating = max(0.0, min(5.0, (float) ($teacher['avg_rating'] ?? 0)));
            if ($ratingCount > 0) {
                $summary['rating_count'] += $ratingCount;
                $ratingWeightedTotal += $avgRating * $ratingCount;
            }
        }

        if ((int) $summary['rating_count'] > 0) {
            $summary['avg_rating'] = round($ratingWeightedTotal / (int) $summary['rating_count'], 2);
        }

        return $summary;
    }

}



