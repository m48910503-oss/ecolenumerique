<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Models\AuthResetModel;
use App\Services\Auth\ResetService;

final class ResetController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new ResetService(new AuthResetModel($pdo));
        $result = $service->handle((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_GET, $_POST);
        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $view = (array) ($result['view'] ?? []);
        $this->render('auth/reset', [
            'siteLogoUrl' => $this->siteLogoUrl(),
            'errors' => (array) ($view['errors'] ?? []),
            'successMessage' => (string) ($view['successMessage'] ?? ''),
            'token' => (string) ($view['token'] ?? ''),
            'tokenValid' => (bool) ($view['tokenValid'] ?? false),
        ]);
    }
}

