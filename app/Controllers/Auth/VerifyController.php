<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Models\AuthVerifyModel;
use App\Services\Auth\VerifyService;

final class VerifyController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $service = new VerifyService(new AuthVerifyModel($pdo));
        $outcome = $service->handle((string) ($_GET['token'] ?? ''));

        $this->render('auth/verify', [
            'siteLogoUrl' => $this->siteLogoUrl(),
            'message' => (string) ($outcome['message'] ?? ''),
            'error' => (string) ($outcome['error'] ?? ''),
        ]);
    }
}

