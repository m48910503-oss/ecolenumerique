<?php
declare(strict_types=1);

namespace App\Services\Student;

use App\Core\Service;
use App\Models\EnrollmentModel;

final class MyCoursesService extends Service
{
    private const COURSES_PER_PAGE = 6;
    private const EXAMS_PER_PAGE = 8;

    private EnrollmentModel $enrollments;

    public function __construct(EnrollmentModel $enrollments)
    {
        $this->enrollments = $enrollments;
    }

    /**
     * @param array<int,string> $roles
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function buildPageData(int $userId, array $roles, array $query = []): array
    {
        $dashboardUrl = $this->siteUrl('student/dashboard');
        if (in_array('admin', $roles, true) || in_array('admin_secondary', $roles, true)) {
            $dashboardUrl = $this->siteUrl('admin/dashboard');
        } elseif (in_array('teacher', $roles, true)) {
            $dashboardUrl = $this->siteUrl('teacher/dashboard');
        } elseif (in_array('company_admin', $roles, true) || in_array('company_member', $roles, true)) {
            $dashboardUrl = $this->siteUrl('company/dashboard');
        }

        $coursesTotal = 0;
        $coursesPager = Pagination::build(0, 1, self::COURSES_PER_PAGE);
        $enrollments = [];
        try {
            $coursesTotal = $this->enrollments->countWithCourseProgressByUserId($userId);
            $coursesPage = Pagination::pageFromQuery($query, 'courses_page');
            $coursesPager = Pagination::build($coursesTotal, $coursesPage, self::COURSES_PER_PAGE);
            $enrollments = $this->enrollments->findWithCourseProgressByUserIdPage(
                $userId,
                (int) $coursesPager['offset'],
                (int) $coursesPager['per_page']
            );
        } catch (\Throwable $e) {
            $coursesTotal = 0;
            $coursesPager = Pagination::build(0, 1, self::COURSES_PER_PAGE);
            $enrollments = [];
        }

        $attemptedExamsTotal = 0;
        $examsPager = Pagination::build(0, 1, self::EXAMS_PER_PAGE);
        $attemptedExams = [];
        try {
            $attemptedExamsTotal = $this->enrollments->countAttemptedExamsByUserId($userId);
            $examsPage = Pagination::pageFromQuery($query, 'exams_page');
            $examsPager = Pagination::build($attemptedExamsTotal, $examsPage, self::EXAMS_PER_PAGE);
            $attemptedExams = $this->enrollments->findAttemptedExamsByUserIdPage(
                $userId,
                (int) $examsPager['offset'],
                (int) $examsPager['per_page']
            );
        } catch (\Throwable $e) {
            $attemptedExamsTotal = 0;
            $examsPager = Pagination::build(0, 1, self::EXAMS_PER_PAGE);
            $attemptedExams = [];
        }

        return [
            'enrollments' => $enrollments,
            'enrollmentsCount' => $coursesTotal,
            'coursesPagination' => $coursesPager,
            'attemptedExams' => $attemptedExams,
            'attemptedExamsCount' => $attemptedExamsTotal,
            'examsPagination' => $examsPager,
            'levelLabels' => [
                'beginner' => 'Débutant',
                'intermediate' => 'Intermédiaire',
                'advanced' => 'Avancé',
            ],
            'dashboardUrl' => $dashboardUrl,
        ];
    }
}
