<?php
declare(strict_types=1);

namespace App\Services\Drive;

use App\Core\Service;

final class OauthCallbackService extends Service
{
    private DriveGateway $drive;

    public function __construct(?DriveGateway $drive = null)
    {
        $this->drive = $drive ?? new DriveGateway();
    }

    public function handle(array $query): array
    {
        if (!$this->drive->oauthConfigured()) {
            if (function_exists('app_log_error')) {
                \app_log_error('drive.oauth', 'Callback OAuth Drive refuse: configuration OAuth manquante.');
            }
            return $this->fail("Drive OAuth non configur\u{00e9}.");
        }

        $state = (string) ($query['state'] ?? '');
        $expectedState = (string) ($_SESSION['drive_oauth_state'] ?? '');
        unset($_SESSION['drive_oauth_state']);

        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            if (function_exists('app_log')) {
                \app_log('warning', 'drive.oauth', 'Etat OAuth Drive invalide.', [
                    'has_state' => $state !== '',
                    'has_expected_state' => $expectedState !== '',
                ]);
            }
            return $this->fail("\u{00c9}tat OAuth invalide.");
        }

        if (!empty($query['error'])) {
            $error = (string) $query['error'];
            $desc = (string) ($query['error_description'] ?? '');
            if (function_exists('app_log')) {
                \app_log('warning', 'drive.oauth', 'OAuth Drive annule ou refuse par Google.', [
                    'error' => $error,
                    'error_description' => $desc,
                ]);
            }
            $suffix = $desc !== '' ? ' - ' . $desc : '';
            return $this->fail('Erreur OAuth: ' . $error . $suffix);
        }

        $code = (string) ($query['code'] ?? '');
        if ($code === '') {
            if (function_exists('app_log')) {
                \app_log('warning', 'drive.oauth', 'Code OAuth Drive manquant.');
            }
            return $this->fail('Code OAuth manquant.');
        }

        $redirectUri = $this->absUrl('drive/oauth_callback');
        $exchange = $this->drive->oauthExchangeCode($code, $redirectUri);
        if (empty($exchange['ok']) || empty($exchange['data'])) {
            if (function_exists('app_log_error')) {
                \app_log_error('drive.oauth', "Echec d'echange OAuth Drive.", [
                    'exchange' => $exchange,
                ]);
            }
            $detail = '';
            if (!empty($exchange['detail']) && is_string($exchange['detail'])) {
                $decoded = json_decode($exchange['detail'], true);
                if (is_array($decoded) && !empty($decoded['error_description'])) {
                    $detail = (string) $decoded['error_description'];
                }
            }
            return $this->fail("\u{00c9}chec d'\u{00e9}change OAuth." . ($detail !== '' ? ' ' . $detail : ''));
        }

        $data = (array) $exchange['data'];
        $existing = $this->drive->loadOauthToken() ?: [];
        $refresh = (string) ($data['refresh_token'] ?? ($existing['refresh_token'] ?? ''));
        if ($refresh === '') {
            if (function_exists('app_log_error')) {
                \app_log_error('drive.oauth', 'Refresh token Drive manquant apres callback OAuth.', [
                    'exchange' => $exchange,
                ]);
            }
            return $this->fail('Refresh token manquant. Reconnectez Drive avec prompt consent.');
        }

        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $tokenData = [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'refresh_token' => $refresh,
            'expires_at' => time() + $expiresIn,
            'scope' => (string) ($data['scope'] ?? ($existing['scope'] ?? '')),
            'token_type' => (string) ($data['token_type'] ?? 'Bearer'),
        ];
        if (!$this->drive->saveOauthToken($tokenData)) {
            if (function_exists('app_log_error')) {
                \app_log_error('drive.oauth', 'Impossible de sauvegarder le token OAuth Drive.', [
                    'token_data' => $tokenData,
                ]);
            }
            return $this->fail('Impossible de sauvegarder le token OAuth.');
        }

        return [
            'ok' => true,
            'httpStatus' => 200,
            'title' => "Drive OAuth connect\u{00e9}",
            'message' => 'La connexion Drive est active.',
            'dashboardUrl' => $this->siteUrl('teacher/dashboard'),
        ];
    }

    private function fail(string $message): array
    {
        return [
            'ok' => false,
            'httpStatus' => 400,
            'title' => 'Drive OAuth',
            'message' => $message,
            'dashboardUrl' => $this->siteUrl('teacher/dashboard'),
        ];
    }
}

