<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;

final class SignatureController extends Controller
{
    public function __invoke(): void
    {
        $user = $this->requireAuth(true, $this->siteUrl('profile?space=teacher#teacher-signature-section'));
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        $isTeacher = in_array('teacher', $roles, true);

        if (!$isAdmin && !$isTeacher) {
            header('Location: ' . $this->siteUrl('profile'));
            exit;
        }

        $this->flashSet('profile_message', 'La signature enseignant se gere desormais depuis votre profil.');
        header('Location: ' . $this->siteUrl('profile?space=teacher#teacher-signature-section'));
        exit;
    }
}
