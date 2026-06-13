<?php
declare(strict_types=1);

namespace App\Services\Company;

use App\Core\Service;
use App\Models\CompanyDashboardModel;
use App\Models\EnrollmentModel;
use App\Models\StudentDashboardModel;

final class MemberDashboardService extends Service
{
    private CompanyDashboardModel $company;
    private StudentDashboardModel $students;
    private EnrollmentModel $enrollments;

    public function __construct(
        CompanyDashboardModel $company,
        StudentDashboardModel $students,
        EnrollmentModel $enrollments
    ) {
        $this->company = $company;
        $this->students = $students;
        $this->enrollments = $enrollments;
    }

    /**
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    public function buildViewData(array $user): array
    {
        $userId = (int) ($user['id'] ?? 0);
        $state = [
            'errors' => [],
            'company' => null,
            'member' => null,
            'team' => null,
            'companyAdmins' => [],
            'assignedCourses' => [],
            'otherCourses' => [],
            'notifications' => [],
            'recentActivity' => [],
            'nextMeeting' => null,
            'unreadCount' => 0,
            'supportUnreadCount' => 0,
            'upcomingMeetingsCount' => 0,
            'kpiCards' => [],
        ];

        if ($userId < 1) {
            $state['errors'][] = 'Session invalide.';
            return $state;
        }

        try {
            $state['unreadCount'] = $this->students->countUnreadNotifications($userId);
        } catch (\Throwable $e) {
            $state['unreadCount'] = 0;
        }

        $supportUnreadReplies = 0;
        $supportUnreadNotifications = 0;
        try {
            $supportUnreadReplies = $this->students->countUnreadSupportReplies($userId);
        } catch (\Throwable $e) {
            $supportUnreadReplies = 0;
        }
        try {
            $supportUnreadNotifications = $this->students->countUnreadSupportNotifications($userId);
        } catch (\Throwable $e) {
            $supportUnreadNotifications = 0;
        }
        $state['supportUnreadCount'] = max($supportUnreadReplies, $supportUnreadNotifications);

        try {
            $state['notifications'] = $this->students->fetchNotifications($userId, 5);
        } catch (\Throwable $e) {
            $state['notifications'] = [];
        }
        try {
            $state['recentActivity'] = $this->students->fetchRecentActivity($userId, 6);
        } catch (\Throwable $e) {
            $state['recentActivity'] = [];
        }
        try {
            $state['nextMeeting'] = $this->students->fetchNextConfirmedMeeting($userId);
        } catch (\Throwable $e) {
            $state['nextMeeting'] = null;
        }
        try {
            $state['upcomingMeetingsCount'] = $this->students->countUpcomingMeetings($userId);
        } catch (\Throwable $e) {
            $state['upcomingMeetingsCount'] = 0;
        }

        try {
            $company = $this->company->findActiveCompanyByUserId($userId);
        } catch (\Throwable $e) {
            $company = null;
        }
        if (!is_array($company)) {
            $state['errors'][] = 'Entreprise introuvable.';
            return $state;
        }

        $companyId = (int) ($company['id'] ?? 0);
        $memberId = (int) ($company['member_id'] ?? 0);
        if ($companyId < 1 || $memberId < 1) {
            $state['errors'][] = 'Entreprise introuvable.';
            return $state;
        }

        $member = [
            'id' => $memberId,
            'role' => strtolower((string) ($company['member_role'] ?? 'member')),
            'role_label' => $this->memberRoleLabel((string) ($company['member_role'] ?? 'member')),
            'status' => strtolower((string) ($company['member_status'] ?? 'active')),
            'status_label' => strtolower((string) ($company['member_status'] ?? 'active')) === 'blocked' ? 'Bloqué' : 'Actif',
            'joined_at' => (string) ($company['member_joined_at'] ?? ''),
            'joined_at_label' => $this->formatDateTime((string) ($company['member_joined_at'] ?? ''), true),
        ];

        $state['company'] = $company;
        $state['member'] = $member;

        $team = null;
        try {
            foreach ($this->company->findTeamAssignmentsByCompanyId($companyId) as $assignment) {
                if ((int) ($assignment['member_id'] ?? 0) !== $memberId) {
                    continue;
                }
                $team = [
                    'id' => (int) ($assignment['team_id'] ?? 0),
                    'name' => (string) ($assignment['team_name'] ?? 'Equipe'),
                ];
                break;
            }
        } catch (\Throwable $e) {
            $team = null;
        }
        $state['team'] = $team;

        $admins = [];
        try {
            foreach ($this->company->findActiveAdminUsersByCompanyId($companyId) as $admin) {
                $adminUserId = (int) ($admin['id'] ?? 0);
                if ($adminUserId === $userId) {
                    continue;
                }
                $fullName = trim((string) ($admin['first_name'] ?? '') . ' ' . (string) ($admin['last_name'] ?? ''));
                if ($fullName === '') {
                    $fullName = (string) ($admin['email'] ?? ('Utilisateur #' . $adminUserId));
                }
                $admins[] = [
                    'id' => $adminUserId,
                    'full_name' => $fullName,
                    'email' => (string) ($admin['email'] ?? ''),
                ];
            }
        } catch (\Throwable $e) {
            $admins = [];
        }
        $state['companyAdmins'] = $admins;

        $enrollmentRows = [];
        try {
            $enrollmentRows = $this->enrollments->findWithCourseProgressByUserId($userId);
        } catch (\Throwable $e) {
            $enrollmentRows = [];
        }

        $enrollmentsByCourse = [];
        $activeEnrollments = 0;
        $completedCourses = 0;
        foreach ($enrollmentRows as $row) {
            $courseId = (int) ($row['id'] ?? 0);
            if ($courseId < 1) {
                continue;
            }
            $doneLessons = max(0, (int) ($row['done_lessons'] ?? 0));
            $totalLessons = max(0, (int) ($row['total_lessons'] ?? 0));
            $progressPct = $totalLessons > 0 ? (int) floor(($doneLessons / $totalLessons) * 100) : 0;
            $enrollStatus = strtolower((string) ($row['enroll_status'] ?? 'active'));
            if ($enrollStatus === 'active') {
                $activeEnrollments++;
            }
            if ($totalLessons > 0 && $doneLessons >= $totalLessons) {
                $completedCourses++;
            }

            $enrollmentsByCourse[$courseId] = [
                'course_id' => $courseId,
                'title' => (string) ($row['title'] ?? 'Cours'),
                'slug' => (string) ($row['slug'] ?? ''),
                'level' => (string) ($row['level'] ?? ''),
                'course_status' => (string) ($row['course_status'] ?? ''),
                'enroll_status' => (string) ($row['enroll_status'] ?? ''),
                'enrolled_at' => (string) ($row['enrolled_at'] ?? ''),
                'enrolled_at_label' => $this->formatDateTime((string) ($row['enrolled_at'] ?? '')),
                'done_lessons' => $doneLessons,
                'total_lessons' => $totalLessons,
                'progress_pct' => $progressPct,
                'course_url' => $this->courseUrl($courseId, (string) ($row['slug'] ?? '')),
            ];
        }

        $assignedCourses = [];
        $assignedCourseIds = [];
        try {
            foreach ($this->company->findCompanySeatAssignmentsByUser($companyId, $userId) as $row) {
                $courseId = (int) ($row['course_id'] ?? 0);
                if ($courseId < 1) {
                    continue;
                }
                $assignedCourseIds[$courseId] = true;
                $enrollment = $enrollmentsByCourse[$courseId] ?? [
                    'course_id' => $courseId,
                    'title' => (string) ($row['course_title'] ?? ('Cours #' . $courseId)),
                    'slug' => '',
                    'level' => '',
                    'course_status' => '',
                    'enroll_status' => 'active',
                    'enrolled_at' => '',
                    'enrolled_at_label' => '-',
                    'done_lessons' => 0,
                    'total_lessons' => 0,
                    'progress_pct' => 0,
                    'course_url' => $this->courseUrl($courseId, ''),
                ];

                $assignedCourses[] = array_merge($enrollment, [
                    'assigned_at' => (string) ($row['assigned_at'] ?? ''),
                    'assigned_at_label' => $this->formatDateTime((string) ($row['assigned_at'] ?? '')),
                    'is_company_assigned' => true,
                ]);
            }
        } catch (\Throwable $e) {
            $assignedCourses = [];
        }
        $state['assignedCourses'] = $assignedCourses;

        $otherCourses = [];
        foreach ($enrollmentsByCourse as $courseId => $row) {
            if (isset($assignedCourseIds[$courseId])) {
                continue;
            }
            $otherCourses[] = array_merge($row, [
                'assigned_at' => '',
                'assigned_at_label' => '-',
                'is_company_assigned' => false,
            ]);
        }
        $state['otherCourses'] = array_slice($otherCourses, 0, 4);

        $state['kpiCards'] = [
            [
                'label' => 'Cours entreprise',
                'value' => count($assignedCourses),
                'meta' => 'Places actives',
                'tone' => 'emerald',
                'icon' => 'book-open',
            ],
            [
                'label' => 'Cours actifs',
                'value' => $activeEnrollments,
                'meta' => 'Tous accès confondus',
                'tone' => 'blue',
                'icon' => 'layout',
            ],
            [
                'label' => 'Notifications',
                'value' => (int) $state['unreadCount'],
                'meta' => 'Non lues',
                'tone' => 'rose',
                'icon' => 'bell',
            ],
            [
                'label' => 'Rendez-vous',
                'value' => (int) $state['upcomingMeetingsCount'],
                'meta' => 'A venir',
                'tone' => 'sky',
                'icon' => 'calendar',
            ],
            [
                'label' => 'Parcours termines',
                'value' => $completedCourses,
                'meta' => 'Cours completes',
                'tone' => 'slate',
                'icon' => 'check-circle',
            ],
        ];

        return $state;
    }

    private function courseUrl(int $courseId, string $slug): string
    {
        $slug = trim($slug);
        if ($slug !== '') {
            return $this->siteUrl('course?slug=' . rawurlencode($slug));
        }
        return $this->siteUrl('course?id=' . $courseId);
    }

    private function memberRoleLabel(string $role): string
    {
        return match (strtolower(trim($role))) {
            'owner' => 'Propriétaire',
            'admin' => 'Administrateur',
            'manager' => 'Gestionnaire',
            'viewer' => 'Lecteur',
            default => 'Collaborateur',
        };
    }

    private function formatDateTime(string $value, bool $dateOnly = false): string
    {
        if ($value === '') {
            return '-';
        }
        $ts = strtotime($value);
        if ($ts === false || $ts <= 0) {
            return '-';
        }
        return $dateOnly ? date('d/m/Y', $ts) : date('d/m/Y H:i', $ts);
    }
}
