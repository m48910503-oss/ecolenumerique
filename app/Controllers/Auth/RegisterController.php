<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Models\AuthRegisterModel;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedReferralModel;
use App\Services\Auth\RegisterService;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\ReferralService;

final class RegisterController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new RegisterService(
            new AuthRegisterModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo)),
            new ReferralService(new SharedReferralModel($pdo))
        );
        $result = $service->handle((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST);
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

        $this->render('auth/register', [
            'siteLogoUrl' => $this->siteLogoUrl(),
            'errors' => $errors,
            'successMessage' => (string) ($view['successMessage'] ?? ''),
            'verificationLink' => (string) ($view['verificationLink'] ?? ''),
            'referralInput' => (string) ($view['referralInput'] ?? ''),
            'referralDetected' => !empty($view['referralDetected']),
            'accountType' => (string) ($view['accountType'] ?? 'personal'),
            'companyName' => (string) ($view['companyName'] ?? ''),
            'old' => (array) ($view['old'] ?? []),
            'uiStep' => (int) ($view['uiStep'] ?? 1),
        ]);
    }
}