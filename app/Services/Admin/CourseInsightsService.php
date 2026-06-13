<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;
use App\Models\AdminCourseInsightsModel;

final class CourseInsightsService extends Service
{
    private AdminCourseInsightsModel $model;

    public function __construct(AdminCourseInsightsModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function buildViewData(array $query = []): array
    {
        $teacherId = max(0, (int) ($query['teacher_id'] ?? 0));
        $search = trim((string) ($query['q'] ?? ''));
        $searchLength = function_exists('mb_strlen') ? mb_strlen($search) : strlen($search);
        if ($searchLength > 120) {
            $search = function_exists('mb_substr') ? mb_substr($search, 0, 120) : substr($search, 0, 120);
        }

        $teacherFilters = $this->model->fetchTeacherFilters();
        $teacherMap = [];
        foreach ($teacherFilters as $teacher) {
            $id = (int) ($teacher['teacher_id'] ?? 0);
            if ($id > 0) {
                $teacherMap[$id] = $teacher;
            }
        }
        if ($teacherId > 0 && !isset($teacherMap[$teacherId])) {
            $teacherId = 0;
        }

        $courseRows = $this->model->fetchApprovedCourseRows($teacherId, $search);
        $summary = $this->buildSummary($courseRows);

        return [
            'teacherFilters' => $teacherFilters,
            'selectedTeacherId' => $teacherId,
            'selectedTeacher' => $teacherId > 0 ? ($teacherMap[$teacherId] ?? null) : null,
            'searchQuery' => $search,
            'courseRows' => $courseRows,
            'summary' => $summary,
            'updatedAt' => date('H:i:s'),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,int>
     */
    private function buildSummary(array $rows): array
    {
        $summary = [
            'course_count' => 0,
            'courses_with_revenue_count' => 0,
            'enrolled_count' => 0,
            'paid_units' => 0,
            'revenue_total_xof' => 0,
            'revenue_direct_xof' => 0,
            'revenue_promo_xof' => 0,
            'revenue_enterprise_xof' => 0,
            'units_direct' => 0,
            'units_promo' => 0,
            'units_enterprise' => 0,
        ];

        foreach ($rows as $row) {
            $summary['course_count']++;

            $enrolled = max(0, (int) ($row['enrolled_count'] ?? 0));
            $paidUnits = max(0, (int) ($row['paid_units'] ?? 0));
            $totalRevenue = max(0, (int) ($row['total_generated_xof'] ?? 0));
            $directRevenue = max(0, (int) ($row['direct_generated_xof'] ?? 0));
            $promoRevenue = max(0, (int) ($row['promo_generated_xof'] ?? 0));
            $enterpriseRevenue = max(0, (int) ($row['enterprise_generated_xof'] ?? 0));
            $directUnits = max(0, (int) ($row['direct_units'] ?? 0));
            $promoUnits = max(0, (int) ($row['promo_units'] ?? 0));
            $enterpriseUnits = max(0, (int) ($row['enterprise_units'] ?? 0));

            if ($totalRevenue > 0) {
                $summary['courses_with_revenue_count']++;
            }

            $summary['enrolled_count'] += $enrolled;
            $summary['paid_units'] += $paidUnits;
            $summary['revenue_total_xof'] += $totalRevenue;
            $summary['revenue_direct_xof'] += $directRevenue;
            $summary['revenue_promo_xof'] += $promoRevenue;
            $summary['revenue_enterprise_xof'] += $enterpriseRevenue;
            $summary['units_direct'] += $directUnits;
            $summary['units_promo'] += $promoUnits;
            $summary['units_enterprise'] += $enterpriseUnits;
        }

        return $summary;
    }
}
