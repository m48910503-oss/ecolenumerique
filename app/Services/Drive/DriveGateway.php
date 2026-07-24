<?php
declare(strict_types=1);

namespace App\Services\Drive;
 
use App\Core\Service;

/**
 * Encapsule toute la logique Drive (OAuth + service account + upload)
 * pour eviter les appels globaux depuis les services metier.
 */
final class DriveGateway extends Service
{
    private const LOCAL_UPLOAD_DIR = '/storage/uploads/drive';

    private function logError(string $message, array $context = []): void
    {
        if (function_exists('app_log_error')) {
            \app_log_error('drive.api', $message, $context);
        }
    }

    private function logWarning(string $message, array $context = []): void
    {
        if (function_exists('app_log')) {
            \app_log('warning', 'drive.api', $message, $context);
        }
    }

    public function oauthConfigured(): bool
    {
        return APP_DRIVE_OAUTH_CLIENT_ID !== '' && APP_DRIVE_OAUTH_CLIENT_SECRET !== '';
    }

    public function serviceAccountConfigured(): bool
    {
        $jsonPath = APP_DRIVE_SERVICE_ACCOUNT_JSON ?? '';
        return $jsonPath !== '' && is_file($jsonPath);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function loadOauthToken(): ?array
    {
        $path = APP_DRIVE_OAUTH_TOKEN_PATH ?? '';
        if ($path === '' || !is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            $this->logError('Lecture du token OAuth Drive impossible.', [
                'path' => $path,
            ]);
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->logError('Token OAuth Drive invalide ou non JSON.', [
                'path' => $path,
            ]);
        }
        return is_array($data) ? $data : null;
    }

    public function clearOauthToken(): bool
    {
        $path = APP_DRIVE_OAUTH_TOKEN_PATH ?? '';
        if ($path === '' || !is_file($path)) {
            return false;
        }
        return unlink($path);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function saveOauthToken(array $data): bool
    {
        $path = APP_DRIVE_OAUTH_TOKEN_PATH ?? '';
        if ($path === '') {
            $this->logError('Sauvegarde du token OAuth Drive impossible: chemin absent.');
            return false;
        }
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            $this->logError('Creation du dossier de token OAuth Drive impossible.', [
                'path' => $path,
                'dir' => $dir,
            ]);
            return false;
        }
        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            $this->logError('Encodage JSON du token OAuth Drive impossible.');
            return false;
        }
        $written = file_put_contents($path, $json, LOCK_EX) !== false;
        if ($written) {
            @chmod($path, 0600);
        } else {
            $this->logError('Ecriture du token OAuth Drive impossible.', [
                'path' => $path,
            ]);
        }
        return $written;
    }

    public function oauthTokenReady(): bool
    {
        $token = $this->loadOauthToken();
        if (!$token) {
            return false;
        }
        $refreshToken = trim((string) ($token['refresh_token'] ?? ''));
        if ($refreshToken !== '') {
            return true;
        }

        $accessToken = trim((string) ($token['access_token'] ?? ''));
        if ($accessToken === '') {
            return false;
        }

        $expiresAt = (int) ($token['expires_at'] ?? 0);
        if ($expiresAt < 1) {
            return false;
        }

        return $expiresAt > (time() + 60);
    }

    public function isConfigured(): bool
    {
        if ($this->oauthConfigured() && $this->oauthTokenReady()) {
            return true;
        }
        if ($this->serviceAccountConfigured()) {
            return true;
        }
        return $this->localUploadAvailable();
    }

    public function remoteUploadReady(): bool
    {
        return $this->hasRemoteBackend();
    }

    public function localUploadReady(): bool
    {
        return $this->localUploadAvailable();
    }

    /**
     * @return array{
     *   ready:bool,
     *   remoteReady:bool,
     *   localReady:bool,
     *   needsConnect:bool,
     *   note:string
     * }
     */
    public function uploadUiState(): array
    {
        $remoteReady = $this->remoteUploadReady();
        $localReady = $this->localUploadReady();
        $needsConnect = $this->oauthConfigured() && !$this->oauthTokenReady();
        $ready = $remoteReady || $localReady;

        if ($remoteReady) {
            $note = 'Google Drive est disponible pour cet envoi.';
        } elseif ($localReady && $needsConnect) {
            $note = "Drive n'est pas connecte. Le fichier sera stocke localement tant que la connexion Drive n'est pas active.";
        } elseif ($localReady && ($this->oauthConfigured() || $this->serviceAccountConfigured())) {
            $note = 'Google Drive est indisponible. Le fichier sera stocke localement.';
        } elseif ($localReady) {
            $note = "Google Drive n'est pas configure. Le fichier sera stocke localement.";
        } elseif ($needsConnect) {
            $note = "Drive n'est pas connecte et le stockage local est indisponible.";
        } elseif ($this->oauthConfigured() || $this->serviceAccountConfigured()) {
            $note = 'Google Drive est indisponible et le stockage local est indisponible.';
        } else {
            $note = "Aucun espace de stockage n'est disponible pour le moment.";
        }

        return [
            'ready' => $ready,
            'remoteReady' => $remoteReady,
            'localReady' => $localReady,
            'needsConnect' => $needsConnect,
            'note' => $note,
        ];
    }

    public function oauthAuthorizeUrl(string $redirectUri, string $state): string
    {
        $params = [
            'client_id' => APP_DRIVE_OAUTH_CLIENT_ID,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * @return array<string,mixed>
     */
    public function oauthExchangeCode(string $code, string $redirectUri): array
    {
        $postFields = http_build_query([
            'code' => $code,
            'client_id' => APP_DRIVE_OAUTH_CLIENT_ID,
            'client_secret' => APP_DRIVE_OAUTH_CLIENT_SECRET,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);
        $ch = curl_init('https://oauth2.googleapis.com/token');
        if (!$ch) {
            $this->logError('Initialisation cURL impossible pour l echange OAuth Drive.');
            return ['ok' => false, 'error' => 'init_failed'];
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $resp = curl_exec($ch);
        $codeStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false || $codeStatus < 200 || $codeStatus >= 300) {
            $this->logError("Echec HTTP pendant l'echange OAuth Drive.", [
                'status' => $codeStatus,
                'detail' => $resp,
                'curl_error' => $err,
            ]);
            return ['ok' => false, 'error' => 'token_failed', 'status' => $codeStatus, 'detail' => $resp, 'curl_error' => $err];
        }
        $json = json_decode((string) $resp, true);
        if (!is_array($json) || empty($json['access_token'])) {
            $this->logError("Reponse invalide pendant l'echange OAuth Drive.", [
                'status' => $codeStatus,
                'detail' => (string) $resp,
            ]);
            return ['ok' => false, 'error' => 'token_invalid'];
        }
        return ['ok' => true, 'data' => $json];
    }

    /**
     * @return array<string,mixed>
     */
    public function oauthRefreshToken(string $refreshToken): array
    {
        $postFields = http_build_query([
            'client_id' => APP_DRIVE_OAUTH_CLIENT_ID,
            'client_secret' => APP_DRIVE_OAUTH_CLIENT_SECRET,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
        $ch = curl_init('https://oauth2.googleapis.com/token');
        if (!$ch) {
            $this->logError('Initialisation cURL impossible pour le refresh OAuth Drive.');
            return ['ok' => false, 'error' => 'init_failed'];
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $resp = curl_exec($ch);
        $codeStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false || $codeStatus < 200 || $codeStatus >= 300) {
            $this->logError('Echec HTTP pendant le refresh OAuth Drive.', [
                'status' => $codeStatus,
                'detail' => $resp,
                'curl_error' => $err,
            ]);
            return ['ok' => false, 'error' => 'refresh_failed', 'status' => $codeStatus, 'detail' => $resp, 'curl_error' => $err];
        }
        $json = json_decode((string) $resp, true);
        if (!is_array($json) || empty($json['access_token'])) {
            $this->logError('Reponse invalide pendant le refresh OAuth Drive.', [
                'status' => $codeStatus,
                'detail' => (string) $resp,
            ]);
            return ['ok' => false, 'error' => 'refresh_invalid'];
        }
        return ['ok' => true, 'data' => $json];
    }

    public function safeFilename(string $name): string
    {
        $name = basename($name);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '_', $base);
        $base = trim((string) $base, '_');
        $ext = preg_replace('/[^A-Za-z0-9]+/', '', $ext);
        $filename = $base !== '' ? $base : 'fichier';
        if ($ext !== '') {
            $filename .= '.' . $ext;
        }
        if (strlen($filename) > 180) {
            $filename = substr($filename, 0, 180);
        }
        return $filename;
    }

    /**
     * @return array<string,mixed>
     */
    public function uploadFile(string $localPath, string $filename, string $mimeType, ?string $parentId = null, bool $makePublic = true): array
    {
        if (!is_file($localPath)) {
            $this->logError('Upload Drive impossible: fichier source absent.', [
                'local_path' => $localPath,
                'filename' => $filename,
            ]);
            return ['ok' => false, 'error' => 'file_missing'];
        }
        if (!$this->hasRemoteBackend()) {
            return $this->storeFileLocally($localPath, $filename);
        }
        if (!function_exists('curl_init')) {
            $this->logError('Upload Drive impossible: cURL indisponible.', [
                'filename' => $filename,
            ]);
            return ['ok' => false, 'error' => 'curl_unavailable'];
        }
        $token = $this->getAccessToken();
        if (!$token) {
            $this->logError('Upload Drive impossible: token d acces indisponible.', [
                'filename' => $filename,
            ]);
            return ['ok' => false, 'error' => 'token_failed'];
        }

        $meta = ['name' => $filename];
        if (!empty($parentId)) {
            $meta['parents'] = [$parentId];
        }
        $fileContent = file_get_contents($localPath);
        if ($fileContent === false) {
            $this->logError('Lecture du fichier avant upload Drive impossible.', [
                'local_path' => $localPath,
                'filename' => $filename,
            ]);
            return ['ok' => false, 'error' => 'read_failed'];
        }

        $boundary = 'drive_boundary_' . bin2hex(random_bytes(12));
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= json_encode($meta) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: {$mimeType}\r\n\r\n";
        $body .= $fileContent . "\r\n";
        $body .= "--{$boundary}--";

        $url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,webViewLink,webContentLink&supportsAllDrives=true';
        $resp = $this->apiRequest(
            'POST',
            $url,
            $token,
            ['Content-Type: multipart/related; boundary=' . $boundary],
            $body
        );
        if ($resp['status'] < 200 || $resp['status'] >= 300 || empty($resp['body'])) {
            $this->logError('Upload Google Drive echoue.', [
                'filename' => $filename,
                'status' => $resp['status'],
                'detail' => $resp['body'],
                'curl_error' => $resp['error'] ?? null,
            ]);
            $payload = ['ok' => false, 'error' => 'upload_failed', 'status' => $resp['status'], 'detail' => $resp['body']];
            if (!empty($resp['error'])) {
                $payload['curl_error'] = $resp['error'];
            }
            return $payload;
        }

        $data = json_decode((string) $resp['body'], true);
        if (!is_array($data) || empty($data['id'])) {
            $this->logError('Reponse invalide apres upload Google Drive.', [
                'filename' => $filename,
                'status' => $resp['status'],
                'detail' => $resp['body'],
            ]);
            return ['ok' => false, 'error' => 'invalid_response'];
        }

        if ($makePublic) {
            $permBody = json_encode(['role' => 'reader', 'type' => 'anyone']);
            $this->apiRequest(
                'POST',
                'https://www.googleapis.com/drive/v3/files/' . rawurlencode((string) $data['id']) . '/permissions?supportsAllDrives=true',
                $token,
                ['Content-Type: application/json'],
                $permBody === false ? null : $permBody
            );
        }

        return [
            'ok' => true,
            'id' => (string) $data['id'],
            'webViewLink' => (string) ($data['webViewLink'] ?? ''),
            'webContentLink' => (string) ($data['webContentLink'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $result
     */
    public function errorMessage(array $result): string
    {
        $error = (string) ($result['error'] ?? '');
        $status = (int) ($result['status'] ?? 0);
        if ($error === 'drive_not_configured') {
            return "Drive non configur\u{00e9}.";
        }
        if ($error === 'local_write_failed') {
            return "Stockage local indisponible.";
        }
        if ($error === 'curl_unavailable') {
            return 'cURL indisponible.';
        }
        if ($error === 'file_missing') {
            return 'Fichier introuvable.';
        }
        if ($error === 'read_failed') {
            return 'Lecture fichier impossible.';
        }
        if ($error === 'token_failed') {
            if ($this->oauthConfigured() && !$this->oauthTokenReady()) {
                return "Drive OAuth non connect\u{00e9}.";
            }
            if ($this->oauthConfigured()) {
                return 'Jeton Drive invalide. Reconnectez Drive.';
            }
            return 'Jeton Drive impossible.';
        }
        if (!empty($result['curl_error'])) {
            return "Erreur r\u{00e9}seau (" . (string) $result['curl_error'] . ').';
        }
        $detail = $result['detail'] ?? null;
        if (is_string($detail) && $detail !== '') {
            $decoded = json_decode($detail, true);
            if (is_array($decoded) && isset($decoded['error'])) {
                $apiError = $decoded['error'];
                $message = '';
                if (is_array($apiError)) {
                    if (!empty($apiError['message']) && is_string($apiError['message'])) {
                        $message = $apiError['message'];
                    } elseif (!empty($apiError['errors'][0]['reason'])) {
                        $message = (string) $apiError['errors'][0]['reason'];
                    }
                }
                if ($message !== '') {
                    $messageLower = strtolower($message);
                    if (
                        strpos($messageLower, 'service accounts do not have storage quota') !== false
                        || strpos($messageLower, 'storage quota') !== false
                    ) {
                        return 'Compte service sans quota Drive. Connectez Drive en OAuth ou utilisez un Shared Drive (Google Workspace).';
                    }
                    return 'Drive: ' . $message . ($status > 0 ? ' (HTTP ' . (string) $status . ')' : '');
                }
            }
            if ($status > 0) {
                return 'HTTP ' . (string) $status . '.';
            }
        }
        if ($status > 0) {
            return 'HTTP ' . (string) $status . '.';
        }
        return '';
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @return array<string,mixed>|null
     */
    private function loadServiceAccount(): ?array
    {
        $jsonPath = APP_DRIVE_SERVICE_ACCOUNT_JSON ?? '';
        if ($jsonPath === '' || !is_file($jsonPath)) {
            return null;
        }
        $raw = file_get_contents($jsonPath);
        if ($raw === false) {
            $this->logError('Lecture du service account Drive impossible.', [
                'path' => $jsonPath,
            ]);
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->logError('JSON du service account Drive invalide.', [
                'path' => $jsonPath,
            ]);
            return null;
        }
        if (empty($data['client_email']) || empty($data['private_key']) || empty($data['token_uri'])) {
            $this->logError('Service account Drive incomplet.', [
                'path' => $jsonPath,
            ]);
            return null;
        }
        return $data;
    }

    private function getAccessTokenServiceAccount(): ?string
    {
        static $cache = ['token' => null, 'exp' => 0];
        if (!empty($cache['token']) && (int) $cache['exp'] > (time() + 60)) {
            return (string) $cache['token'];
        }

        $sa = $this->loadServiceAccount();
        if (!$sa) {
            return null;
        }
        $now = time();
        $headerJson = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $claimsJson = json_encode([
            'iss' => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud' => $sa['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600,
        ]);
        if ($headerJson === false || $claimsJson === false) {
            $this->logError('Encodage JWT impossible pour le service account Drive.');
            return null;
        }
        $header = $this->base64UrlEncode($headerJson);
        $payload = $this->base64UrlEncode($claimsJson);
        $unsigned = $header . '.' . $payload;
        $signature = '';
        $signedOk = openssl_sign($unsigned, $signature, (string) $sa['private_key'], 'SHA256');
        if (!$signedOk) {
            $this->logError('Signature JWT impossible pour le service account Drive.');
            return null;
        }
        $jwt = $unsigned . '.' . $this->base64UrlEncode($signature);
        $postFields = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);
        $ch = curl_init((string) $sa['token_uri']);
        if (!$ch) {
            $this->logError('Initialisation cURL impossible pour le service account Drive.', [
                'token_uri' => (string) $sa['token_uri'],
            ]);
            return null;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false || $code < 200 || $code >= 300) {
            $this->logError('Echec HTTP pour le service account Drive.', [
                'status' => $code,
                'detail' => $resp,
                'curl_error' => $err,
            ]);
            return null;
        }
        $json = json_decode((string) $resp, true);
        if (!is_array($json) || empty($json['access_token'])) {
            $this->logError('Reponse invalide pour le service account Drive.', [
                'status' => $code,
                'detail' => (string) $resp,
            ]);
            return null;
        }
        $expiresIn = (int) ($json['expires_in'] ?? 3600);
        $cache = ['token' => $json['access_token'], 'exp' => $now + $expiresIn];
        return (string) $json['access_token'];
    }

    private function getAccessTokenOauth(): ?string
    {
        static $cache = ['token' => null, 'exp' => 0];
        if (!empty($cache['token']) && (int) $cache['exp'] > (time() + 60)) {
            return (string) $cache['token'];
        }
        $token = $this->loadOauthToken();
        if (!$token) {
            return null;
        }
        $now = time();
        $expiresAt = (int) ($token['expires_at'] ?? 0);
        if (!empty($token['access_token']) && $expiresAt > ($now + 60)) {
            $cache = ['token' => (string) $token['access_token'], 'exp' => $expiresAt];
            return (string) $token['access_token'];
        }

        $refresh = (string) ($token['refresh_token'] ?? '');
        if ($refresh === '') {
            $this->logWarning('Refresh token Drive absent, suppression du token OAuth local.');
            $this->clearOauthToken();
            return null;
        }

        $resp = $this->oauthRefreshToken($refresh);
        if (empty($resp['ok']) || empty($resp['data']) || !is_array($resp['data'])) {
            $this->logError('Refresh du token OAuth Drive echoue, suppression du token local.', [
                'response' => $resp,
            ]);
            $this->clearOauthToken();
            return null;
        }
        $data = $resp['data'];
        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $newToken = [
            'access_token' => (string) $data['access_token'],
            'refresh_token' => $refresh,
            'expires_at' => $now + $expiresIn,
            'scope' => (string) ($data['scope'] ?? ($token['scope'] ?? '')),
            'token_type' => (string) ($data['token_type'] ?? 'Bearer'),
        ];
        $this->saveOauthToken($newToken);
        $cache = ['token' => (string) $newToken['access_token'], 'exp' => (int) $newToken['expires_at']];
        return (string) $newToken['access_token'];
    }

    private function getAccessToken(): ?string
    {
        if ($this->oauthConfigured() && $this->oauthTokenReady()) {
            $oauthToken = $this->getAccessTokenOauth();
            if ($oauthToken !== null && $oauthToken !== '') {
                return $oauthToken;
            }
        }
        return $this->getAccessTokenServiceAccount();
    }

    private function hasRemoteBackend(): bool
    {
        return ($this->oauthConfigured() && $this->oauthTokenReady()) || $this->serviceAccountConfigured();
    }

    private function localUploadAvailable(): bool
    {
        $dir = $this->localUploadDirectory();
        if (is_dir($dir)) {
            return is_writable($dir);
        }
        return @mkdir($dir, 0755, true) || is_dir($dir);
    }

    private function localUploadDirectory(): string
    {
        return dirname(__DIR__, 3) . self::LOCAL_UPLOAD_DIR;
    }

    /**
     * @return array<string,mixed>
     */
    private function storeFileLocally(string $localPath, string $filename): array
    {
        $dir = $this->localUploadDirectory() . '/' . date('Y') . '/' . date('m');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            $this->logError('Creation du dossier de stockage local Drive impossible.', [
                'dir' => $dir,
                'filename' => $filename,
            ]);
            return ['ok' => false, 'error' => 'local_write_failed'];
        }

        $safe = $this->safeFilename($filename);
        $ext = pathinfo($safe, PATHINFO_EXTENSION);
        $base = pathinfo($safe, PATHINFO_FILENAME);
        $suffix = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $storedName = ($base !== '' ? $base : 'fichier') . '_' . $suffix . ($ext !== '' ? '.' . $ext : '');
        $target = $dir . '/' . $storedName;

        if (!@copy($localPath, $target)) {
            $this->logError('Copie locale du fichier Drive impossible.', [
                'local_path' => $localPath,
                'target' => $target,
                'filename' => $filename,
            ]);
            return ['ok' => false, 'error' => 'local_write_failed'];
        }

        $relativePath = ltrim(str_replace('\\', '/', self::LOCAL_UPLOAD_DIR . '/' . date('Y') . '/' . date('m') . '/' . $storedName), '/');
        $publicUrl = $this->absUrl($relativePath);

        return [
            'ok' => true,
            'id' => 'local:' . $storedName,
            'webViewLink' => $publicUrl,
            'webContentLink' => $publicUrl,
        ];
    }

    /**
     * @param array<int,string> $headers
     * @return array{status:int,body:mixed,error:?string}
     */
    private function apiRequest(string $method, string $url, string $token, array $headers = [], ?string $body = null): array
    {
        $ch = curl_init($url);
        if (!$ch) {
            return ['status' => 0, 'body' => null, 'error' => 'init_failed'];
        }
        $baseHeaders = ['Authorization: Bearer ' . $token];
        $allHeaders = array_merge($baseHeaders, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return ['status' => $code, 'body' => $resp, 'error' => $err !== '' ? $err : null];
    }
}

