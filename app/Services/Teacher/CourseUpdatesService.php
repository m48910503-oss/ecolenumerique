<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;

use App\Models\TeacherCourseUpdatesModel;

final class CourseUpdatesService extends Service
{
    private TeacherCourseUpdatesModel $model;

    public function __construct(TeacherCourseUpdatesModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @return array{view:array<string,mixed>}
     */
    public function build(array $user, bool $isAdmin): array
    {
        $updates = [];
        try {
            $updates = $this->model->fetchUpdates($isAdmin, (int) ($user['id'] ?? 0), 100);
        } catch (\Throwable $e) {
            $updates = [];
        }

        $fmtDtLocal = static function (?string $dbValue): string {
            if (!$dbValue) {
                return '';
            }
            $ts = strtotime($dbValue);
            if (!$ts) {
                return (string) $dbValue;
            }
            return date('d/m/Y H:i', $ts);
        };

        return [
            'view' => [
                'updates' => $updates,
                'fmtDtLocal' => $fmtDtLocal,
            ],
        ];
    }
}

