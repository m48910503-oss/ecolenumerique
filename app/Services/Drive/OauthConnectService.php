<?php
declare(strict_types=1);

namespace App\Services\Drive;

use App\Core\Service;

final class OauthConnectService extends Service
{
    private DriveGateway $drive;

    public function __construct(?DriveGateway $drive = null)
    {
        $this->drive = $drive ?? new DriveGateway();
    }

    public function buildAuthorizeUrl(): array
    {
        if (!$this->drive->oauthConfigured()) {
            if (function_exists('app_log_error')) {
                \app_log_error('drive.oauth', 'Demarrage OAuth Drive refuse: configuration OAuth manquante.', [
                    'oauth_client_id_present' => defined('APP_DRIVE_OAUTH_CLIENT_ID') && APP_DRIVE_OAUTH_CLIENT_ID !== '',
                    'oauth_client_secret_present' => defined('APP_DRIVE_OAUTH_CLIENT_SECRET') && APP_DRIVE_OAUTH_CLIENT_SECRET !== '',
                ]);
            }
            return [
                'ok' => false,
                'error' => 'Drive OAuth non configure.',
                'url' => '',
            ];
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['drive_oauth_state'] = $state;

        $redirectUri = $this->absUrl('drive/oauth_callback');
        $authUrl = $this->drive->oauthAuthorizeUrl($redirectUri, $state);

        return [
            'ok' => true,
            'error' => '',
            'url' => $authUrl,
        ];
    }
}

