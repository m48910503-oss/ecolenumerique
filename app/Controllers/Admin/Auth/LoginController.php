<?php
declare(strict_types=1);

namespace App\Controllers\Admin\Auth;

use App\Core\Controller;
use App\Models\AdminLoginModel;
use App\Services\Admin\Auth\LoginService;

final class LoginController extends Controller
{
    public function __invoke(): void
    {
        $siteLogoUrl = $this->siteLogoUrl();
        $successMessage = (string) $this->flashGet('success', '');

        if ($this->isAuthenticated()) {
            header('Location: ' . $this->siteUrl('admin/dashboard'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        /** @var \PDO $pdo */
        $pdo = $this->pdo();
        $service = new LoginService(new AdminLoginModel($pdo));
        $result = $service->handle(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('admin/auth/login', array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'successMessage' => $successMessage,
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

