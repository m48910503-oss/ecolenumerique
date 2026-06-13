<?php
declare(strict_types=1);

namespace App\Controllers\Drive;

use App\Core\Controller;
use App\Services\Drive\OauthConnectService;

final class OauthConnectController extends Controller
{
    public function __invoke(): void
    {
        $this->requireAuth(true, $this->siteUrl('login'));

        $service = new OauthConnectService();
        $result = $service->buildAuthorizeUrl();
        if (empty($result['ok'])) {
            http_response_code(500);
            exit((string) ($result['error'] ?? 'Drive OAuth indisponible.'));
        }

        header('Location: ' . (string) $result['url']);
        exit;
    }
}
