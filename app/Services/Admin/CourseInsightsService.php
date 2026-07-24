<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;
use App\Models\AdminCourseInsightsModel;
use App\Models\AdminCoursesModel;

final class CourseInsightsService extends Service
{
    private AdminCourseInsightsModel $model;
    private AdminCoursesModel $coursesModel;

    public function __construct(AdminCourseInsightsModel $model, AdminCoursesModel $coursesModel)
    {
        $this->model = $model;
        $this->coursesModel = $coursesModel;
    }

    /**
     * @param array<string,mixed> $query
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function buildViewData(array $query = []): array
    {
        $courseId = max(0, (int) ($query['id'] ?? $query['course_id'] ?? 0));
        $teacherId = max(0, (int) ($query['teacher_id'] ?? 0));

        if ($courseId > 0) {
            return $this->buildCourseViewData($courseId);
        }

        if ($teacherId > 0) {
            return $this->buildTeacherViewData($teacherId);
        }

        return [
            'redirectTo' => $this->siteUrl('admin/courses'),
            'view' => [],
        ];
    }

    /**
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    private function buildCourseViewData(int $courseId): array
    {
        $course = $this->model->findApprovedCourseById($courseId);
        if (!is_array($course)) {
            return [
                'redirectTo' => $this->siteUrl('admin/courses'),
                'view' => [],
            ];
        }

        $statsMap = $this->coursesModel->fetchCourseStatsForIds([$courseId]);
        $extraStats = is_array($statsMap[$courseId] ?? null) ? $statsMap[$courseId] : [];

        $students = max(
            0,
            max(
                (int) ($course['enrolled_count'] ?? 0),
                (int) ($extraStats['students'] ?? 0)
            )
        );
        $paidUnits = max(0, (int) ($course['paid_units'] ?? 0));
        $totalRevenue = max(0, (int) ($course['total_generated_xof'] ?? 0));
        $directRevenue = max(0, (int) ($course['direct_generated_xof'] ?? 0));
        $promoRevenue = max(0, (int) ($course['promo_generated_xof'] ?? 0));
        $enterpriseRevenue = max(0, (int) ($course['enterprise_generated_xof'] ?? 0));
        $directUnits = max(0, (int) ($course['direct_units'] ?? 0));
        $promoUnits = max(0, (int) ($course['promo_units'] ?? 0));
        $enterpriseUnits = max(0, (int) ($course['enterprise_units'] ?? 0));
        $ratingAverage = max(0.0, min(5.0, (float) ($extraStats['avg_rating'] ?? 0.0)));
        $ratingCount = max(0, (int) ($extraStats['rating_count'] ?? 0));
        $completionRate = max(0, min(100, (int) ($extraStats['completion_rate'] ?? 0)));
        $monthlyEnrollments = is_array($extraStats['monthly_enrollments'] ?? null)
            ? array_values($extraStats['monthly_enrollments'])
            : [];

        $channels = is_array($extraStats['enrollment_channels'] ?? null)
            ? $extraStats['enrollment_channels']
            : [];
        $channelTotal = max(0, (int) ($channels['total'] ?? 0));
        $channelDirect = max(0, (int) ($channels['direct'] ?? 0));
        $channelPromo = max(0, (int) ($channels['promo'] ?? 0));
        $channelEnterprise = max(0, (int) ($channels['enterprise'] ?? 0));

        $resolvedTotal = max($students, $channelTotal);
        if (($channelDirect + $channelPromo + $channelEnterprise) < $resolvedTotal) {
            $channelDirect += $resolvedTotal - ($channelDirect + $channelPromo + $channelEnterprise);
        }

        return [
            'redirectTo' => null,
            'view' => [
                'course' => $course,
                'stats' => [
                    'students' => $students,
                    'paid_units' => $paidUnits,
                    'revenue_total_xof' => $totalRevenue,
                    'revenue_direct_xof' => $directRevenue,
                    'revenue_promo_xof' => $promoRevenue,
                    'revenue_enterprise_xof' => $enterpriseRevenue,
                    'units_direct' => $directUnits,
                    'units_promo' => $promoUnits,
                    'units_enterprise' => $enterpriseUnits,
                    'avg_rating' => $ratingAverage,
                    'rating_count' => $ratingCount,
                    'completion_rate' => $completionRate,
                    'enrollment_channels' => [
                        'total' => $resolvedTotal,
                        'direct' => $channelDirect,
                        'promo' => $channelPromo,
                        'enterprise' => $channelEnterprise,
                    ],
                    'monthly_enrollments' => $monthlyEnrollments,
                ],
                'updatedAt' => date('H:i:s'),
            ],
        ];
    }

    /**
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    private function buildTeacherViewData(int $teacherId): array
    {
        $teacher = $this->model->findTeacherById($teacherId);
        if (!is_array($teacher)) {
            return [
                'redirectTo' => $this->siteUrl('admin/courses'),
                'view' => [],
            ];
        }

        $teacherCourses = $this->coursesModel->fetchApprovedCourses(0, 0, ['teacher_id' => $teacherId]);
        $statsMap = $this->coursesModel->fetchCourseStatsForIds($this->extractCourseIds($teacherCourses));
        $teacherCourses = $this->attachCourseStats($teacherCourses, $statsMap);
        $teacherStats = $this->buildTeacherStats($teacherCourses);

        return [
            'redirectTo' => null,
            'view' => [
                'mode' => 'teacher',
                'teacher' => $teacher,
                'teacherCourses' => $teacherCourses,
                'teacherStats' => $teacherStats,
                'updatedAt' => date('H:i:s'),
            ],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $teacherCourses
     * @return array<string,mixed>
     */
    private function buildTeacherStats(array $teacherCourses): array
    {
        $summary = [
            'approved_courses' => count($teacherCourses),
            'students' => 0,
            'paid_units' => 0,
            'revenue_total_xof' => 0,
            'avg_rating' => 0.0,
            'rating_count' => 0,
            'completion_rate' => 0,
            'enrollment_channels' => [
                'total' => 0,
                'direct' => 0,
                'promo' => 0,
                'enterprise' => 0,
            ],
            'monthly_enrollments' => [],
        ];

        $ratingPoints = 0.0;
        $completionPoints = 0.0;
        $monthlyTotals = [];

        foreach ($teacherCourses as $course) {
            $stats = is_array($course['stats'] ?? null) ? $course['stats'] : [];
            $students = max(0, (int) ($stats['students'] ?? 0));
            $paidUnits = max(0, (int) ($stats['paid_orders'] ?? 0));
            $revenue = max(0, (int) ($stats['revenue_xof'] ?? 0));
            $ratingAverage = max(0.0, min(5.0, (float) ($stats['avg_rating'] ?? 0.0)));
            $ratingCount = max(0, (int) ($stats['rating_count'] ?? 0));
            $completionRate = max(0, min(100, (int) ($stats['completion_rate'] ?? 0)));
            $channels = is_array($stats['enrollment_channels'] ?? null) ? $stats['enrollment_channels'] : [];
            $monthlyEnrollments = is_array($stats['monthly_enrollments'] ?? null) ? $stats['monthly_enrollments'] : [];

            $summary['students'] += $students;
            $summary['paid_units'] += $paidUnits;
            $summary['revenue_total_xof'] += $revenue;
            $summary['rating_count'] += $ratingCount;
            $ratingPoints += $ratingAverage * $ratingCount;
            $completionPoints += $completionRate;

            foreach (['total', 'direct', 'promo', 'enterprise'] as $channelKey) {
                $summary['enrollment_channels'][$channelKey] = max(
                    0,
                    (int) ($summary['enrollment_channels'][$channelKey] ?? 0) + (int) ($channels[$channelKey] ?? 0)
                );
            }

            foreach ($monthlyEnrollments as $monthRow) {
                $label = trim((string) ($monthRow['label'] ?? ''));
                if ($label === '') {
                    continue;
                }
                if (!isset($monthlyTotals[$label])) {
                    $monthlyTotals[$label] = 0;
                }
                $monthlyTotals[$label] += max(0, (int) ($monthRow['count'] ?? 0));
            }
        }

        $summary['avg_rating'] = $summary['rating_count'] > 0
            ? round($ratingPoints / $summary['rating_count'], 2)
            : 0.0;
        $summary['completion_rate'] = $summary['approved_courses'] > 0
            ? (int) round($completionPoints / $summary['approved_courses'])
            : 0;

        $summary['monthly_enrollments'] = [];
        foreach ($monthlyTotals as $label => $count) {
            $summary['monthly_enrollments'][] = [
                'label' => $label,
                'count' => $count,
            ];
        }

        return $summary;
    }

    /**
     * @param array<int,array<string,mixed>> $teacherCourses
     * @return array<int,int>
     */
    private function extractCourseIds(array $teacherCourses): array
    {
        $ids = [];
        foreach ($teacherCourses as $course) {
            $courseId = (int) ($course['id'] ?? 0);
            if ($courseId > 0) {
                $ids[$courseId] = $courseId;
            }
        }

        return array_values($ids);
    }

    /**
     * @param array<int,array<string,mixed>> $teacherCourses
     * @param array<int,array<string,mixed>> $statsMap
     * @return array<int,array<string,mixed>>
     */
    private function attachCourseStats(array $teacherCourses, array $statsMap): array
    {
        foreach ($teacherCourses as &$course) {
            $courseId = (int) ($course['id'] ?? 0);
            $course['stats'] = is_array($statsMap[$courseId] ?? null)
                ? $statsMap[$courseId]
                : [
                    'students' => 0,
                    'revenue_xof' => 0,
                    'paid_orders' => 0,
                    'avg_rating' => 0.0,
                    'rating_count' => 0,
                    'completion_rate' => 0,
                    'enrollment_channels' => [
                        'total' => 0,
                        'direct' => 0,
                        'promo' => 0,
                        'enterprise' => 0,
                    ],
                    'monthly_enrollments' => [],
                ];
        }
        unset($course);

        return $teacherCourses;
    }
}
