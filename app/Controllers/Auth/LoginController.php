<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Models\AuthLoginModel;
use App\Services\Auth\LoginService;

final class LoginController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $errors = [];
        $successMessage = (string) $this->flashGet('success', '');
        if ($successMessage === '' && !empty($_GET['account_deleted'])) {
            $successMessage = 'Votre compte a ete supprime definitivement.';
        }
        $return = $this->siteUrl('');
        $oldEmail = '';

        if ($this->isAuthenticated()) {
            header('Location: ' . $return);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new LoginService(new AuthLoginModel($pdo));
        $result = $service->handle((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST, $_GET);
        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }
        $view = (array) ($result['view'] ?? []);
        $errors = (array) ($view['errors'] ?? []);
        $flashError = trim((string) $this->flashGet('error', ''));
        if ($flashError !== '') {
            $errors[] = $flashError;
        }
        $oldEmail = (string) ($view['oldEmail'] ?? '');
        $return = (string) ($view['return'] ?? $return);

        $this->render('auth/login', [
            'siteLogoUrl' => $siteLogoUrl,
            'errors' => $errors,
            'successMessage' => $successMessage,
            'return' => $return,
            'oldEmail' => $oldEmail,
        ]);
    }
}


