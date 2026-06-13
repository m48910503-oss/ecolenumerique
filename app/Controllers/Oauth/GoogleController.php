<?php
declare(strict_types=1);

namespace App\Controllers\Oauth;

use App\Core\Controller;
use App\Models\OauthGoogleModel;
use App\Models\SharedReferralModel;
use App\Services\Oauth\GoogleService;
use App\Services\Shared\ReferralService;

final class GoogleController extends Controller
{
    public function __invoke(): void
    {
        if ($this->isAuthenticated()) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $service = new GoogleService(
            new OauthGoogleModel($pdo),
            new ReferralService(new SharedReferralModel($pdo))
        );
        $result = $service->handle($_GET);

        header('Location: ' . (string) ($result['redirectTo'] ?? $this->siteUrl('login')));
        exit;
    }
}
