<?php
declare(strict_types=1);

namespace App\Controllers\Admin\Auth;

use App\Core\Controller;
use App\Models\AdminRegisterModel;
use App\Services\Admin\Auth\RegisterService;

final class RegisterController extends Controller
{
    public function __invoke(): void
    {
        $siteLogoUrl = $this->siteLogoUrl();
        $registerToken = trim((string) ($_POST['token'] ?? ($_GET['token'] ?? '')));
        if (!admin_register_access_allowed($registerToken)) {
            http_response_code(404);
            $this->render('web/public_error', $this->publicErrorViewData(
                'Page indisponible',
                "L'inscription administrateur est desactivee."
            ));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        /** @var \PDO $pdo */
        $pdo = $this->pdo();
        $service = new RegisterService(new AdminRegisterModel($pdo));
        $result = $service->handle(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST,
            $registerToken
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('admin/auth/register', array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'registerToken' => $registerToken,
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}
