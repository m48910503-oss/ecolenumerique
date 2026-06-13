<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Models\AuthForgotModel;
use App\Services\Auth\ForgotService;

final class ForgotController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new ForgotService(new AuthForgotModel($pdo));
        $result = $service->handle((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST);
        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $view = (array) ($result['view'] ?? []);
        $this->render('auth/forgot', [
            'message' => (string) ($view['message'] ?? ''),
            'debugLink' => '',
            'siteLogoUrl' => $this->siteLogoUrl(),
        ]);
    }
}
