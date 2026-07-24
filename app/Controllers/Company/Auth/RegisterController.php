<?php
declare(strict_types=1);

namespace App\Controllers\Company\Auth;

use App\Core\Controller;
use App\Models\AuthRegisterModel;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedReferralModel;
use App\Services\Company\Auth\RegisterService;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\ReferralService;

final class RegisterController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();
        $currentUser = $this->currentUser();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new RegisterService(
            new AuthRegisterModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo)),
            new ReferralService(new SharedReferralModel($pdo))
        );
        $result = $service->handle((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST, $currentUser);
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
        $this->render('company/auth/register', [
            'siteLogoUrl' => $this->siteLogoUrl(),
            'errors' => $errors,
            'successMessage' => (string) ($view['successMessage'] ?? ''),
            'verificationLink' => (string) ($view['verificationLink'] ?? ''),
            'isAuthenticated' => !empty($view['isAuthenticated']),
            'isGoogleOnboarding' => !empty($view['isGoogleOnboarding']),
            'old' => (array) ($view['old'] ?? []),
        ]);
    }
}
