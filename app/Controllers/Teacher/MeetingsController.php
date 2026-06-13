<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\TeacherMeetingsModel;
use App\Services\Teacher\MeetingsService;

final class MeetingsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();

        $user = $this->requireAuth(true, $this->siteUrl('teacher/meetings'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        if (!$isAdmin && !in_array('teacher', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new MeetingsService($pdo, new TeacherMeetingsModel($pdo));
        $result = $service->handle(
            $user,
            $isAdmin,
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_GET,
            $_POST
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('teacher/meetings', array_merge(
            ['siteLogoUrl' => $siteLogoUrl],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

