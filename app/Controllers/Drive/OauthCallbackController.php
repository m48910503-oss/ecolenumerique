<?php
declare(strict_types=1);

namespace App\Controllers\Drive;

use App\Core\Controller;
use App\Services\Drive\OauthCallbackService;

final class OauthCallbackController extends Controller
{
    public function __invoke(): void
    {
        $user = $this->requireAuth(true, $this->siteUrl('login'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        $isTeacher = in_array('teacher', $roles, true);
        $redirectUrl = ($isAdmin || $isTeacher) ? $this->siteUrl('teacher/dashboard') : $this->siteUrl('');

        $service = new OauthCallbackService();
        $result = $service->handle($_GET);

        http_response_code((int) ($result['httpStatus'] ?? 200));
        $this->render('drive/oauth_result', [
            'title' => (string) ($result['title'] ?? 'Drive OAuth'),
            'message' => (string) ($result['message'] ?? ''),
            'dashboardUrl' => $redirectUrl,
            'ok' => !empty($result['ok']),
        ]);
    }
}
