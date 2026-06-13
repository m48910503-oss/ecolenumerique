<?php
declare(strict_types=1);

namespace App\Services\Oauth;

use App\Core\Service;
use App\Models\OauthGoogleModel;
use App\Services\Shared\ReferralService; 

final class GoogleService extends Service
{
    private OauthGoogleModel $model;
    private ReferralService $referrals;
    private string $clientId;
    private string $clientSecret;

    private function logError(string $message, array $context = []): void
    {
        if (function_exists('app_log_error')) {
            \app_log_error('google.oauth', $message, $context);
        }
    }

    private function logWarning(string $message, array $context = []): void
    {
        if (function_exists('app_log')) {
            \app_log('warning', 'google.oauth', $message, $context);
        }
    }

    public function __construct(OauthGoogleModel $model, ReferralService $referrals)
    {
        $this->model = $model;
        $this->referrals = $referrals;
        $this->clientId = defined('APP_GOOGLE_OAUTH_CLIENT_ID') ? (string) APP_GOOGLE_OAUTH_CLIENT_ID : '';
        $this->clientSecret = defined('APP_GOOGLE_OAUTH_CLIENT_SECRET') ? (string) APP_GOOGLE_OAUTH_CLIENT_SECRET : '';
    }

    /**
     * @param array<string,mixed> $query
     * @return array{redirectTo:string}
     */
    public function handle(array $query): array
    {
        if (!$this->isConfigured()) {
            $this->logError('Configuration OAuth Google manquante.', [
                'client_id_present' => $this->clientId !== '',
                'client_secret_present' => $this->clientSecret !== '',
            ]);
            $this->flashSet('error', 'Connexion Google indisponible: configuration OAuth manquante.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $isCallback = isset($query['code']) || isset($query['error']) || isset($query['state']);
        if (!$isCallback) {
            return $this->startFlow($query);
        }

        return $this->handleCallback($query);
    }

    /**
     * @param array<string,mixed> $query
     * @return array{redirectTo:string}
     */
    private function startFlow(array $query): array
    {
        $source = $this->normalizeSource((string) ($query['src'] ?? 'login'));
        $rawReturn = (string) ($query['return'] ?? '');
        if ($source === 'company_invite') {
            $inviteToken = trim((string) ($query['invite_token'] ?? ($query['token'] ?? '')));
            if ($inviteToken !== '') {
                $_SESSION['google_oauth_invite_token'] = $inviteToken;
            }
        } else {
            unset($_SESSION['google_oauth_invite_token']);
        }

        $fallbackReturn = $this->sourceEntryPath($source);
        $returnPath = $rawReturn !== '' ? $this->sanitizeReturnPath($rawReturn, $fallbackReturn) : $fallbackReturn;

        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;
        $_SESSION['google_oauth_source'] = $source;
        $_SESSION['google_oauth_return'] = $returnPath;

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->absUrl('oauth/google'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'include_granted_scopes' => 'true',
            'prompt' => 'select_account',
            'state' => $state,
        ];

        return [
            'redirectTo' => 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params),
        ];
    }

    /**
     * @param array<string,mixed> $query
     * @return array{redirectTo:string}
     */
    private function handleCallback(array $query): array
    {
        $source = $this->normalizeSource((string) ($_SESSION['google_oauth_source'] ?? 'login'));
        $returnPath = (string) ($_SESSION['google_oauth_return'] ?? $this->siteUrl(''));
        $expectedState = (string) ($_SESSION['google_oauth_state'] ?? '');
        $inviteToken = trim((string) ($_SESSION['google_oauth_invite_token'] ?? ''));
        unset(
            $_SESSION['google_oauth_source'],
            $_SESSION['google_oauth_return'],
            $_SESSION['google_oauth_state'],
            $_SESSION['google_oauth_invite_token']
        );

        $sourcePath = $this->sourceEntryPath($source, $inviteToken);

        if (!empty($query['error'])) {
            $error = (string) $query['error'];
            $desc = trim((string) ($query['error_description'] ?? ''));
            $this->logWarning('OAuth Google annule ou refuse par Google.', [
                'source' => $source,
                'error' => $error,
                'error_description' => $desc,
            ]);
            $suffix = $desc !== '' ? ' (' . $desc . ')' : '';
            $this->flashSet('error', "Connexion Google annul\u{00e9}e : " . $error . $suffix);
            return ['redirectTo' => $sourcePath];
        }

        $state = (string) ($query['state'] ?? '');
        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            $this->logWarning('Etat OAuth Google invalide.', [
                'source' => $source,
                'has_state' => $state !== '',
                'has_expected_state' => $expectedState !== '',
            ]);
            $this->flashSet('error', "Connexion Google invalide : \u{00e9}tat OAuth incorrect.");
            return ['redirectTo' => $sourcePath];
        }

        $code = (string) ($query['code'] ?? '');
        if ($code === '') {
            $this->logWarning('Code OAuth Google manquant.', [
                'source' => $source,
            ]);
            $this->flashSet('error', 'Connexion Google invalide : code OAuth manquant.');
            return ['redirectTo' => $sourcePath];
        }

        $exchange = $this->exchangeCode($code);
        if (empty($exchange['ok'])) {
            $this->logError("Echec d'echange du code OAuth Google.", [
                'source' => $source,
                'exchange' => $exchange,
            ]);
            $this->flashSet('error', "Connexion Google impossible : \u{00e9}chec d'\u{00e9}change de jeton.");
            return ['redirectTo' => $sourcePath];
        }

        $accessToken = (string) ($exchange['access_token'] ?? '');
        if ($accessToken === '') {
            $this->logError('Access token Google absent apres echange OAuth.', [
                'source' => $source,
                'exchange' => $exchange,
            ]);
            $this->flashSet('error', 'Connexion Google impossible: access token manquant.');
            return ['redirectTo' => $sourcePath];
        }

        $profile = $this->fetchUserProfile($accessToken);
        if (empty($profile['ok'])) {
            $this->logError('Profil Google indisponible apres authentification OAuth.', [
                'source' => $source,
                'profile' => $profile,
            ]);
            $this->flashSet('error', 'Connexion Google impossible: profil utilisateur indisponible.');
            return ['redirectTo' => $sourcePath];
        }

        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        $emailVerified = !empty($profile['email_verified']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$emailVerified) {
            $this->logWarning('Connexion Google refusee: email absent ou non verifie.', [
                'source' => $source,
                'email' => $email,
                'email_verified' => $emailVerified,
            ]);
            $this->flashSet('error', "Connexion Google refus\u{00e9}e : email Google non v\u{00e9}rifi\u{00e9}.");
            return ['redirectTo' => $sourcePath];
        }

        $names = $this->resolveNames($profile);

        try {
            $this->model->beginTransaction();

            $user = $this->model->findUserByEmail($email);
            $isNewUser = false;
            $mustHaveRoles = $this->rolesForSource($source);
            if ($user) {
                $status = (string) ($user['status'] ?? '');
                if ($status === 'suspended') {
                    throw new \RuntimeException('suspended');
                }

                if ($status !== 'active' || empty($user['email_verified_at'])) {
                    $this->model->activateAndVerifyUser((int) $user['id']);
                    $user = $this->model->findUserById((int) $user['id']);
                }
                if ($user) {
                    $updatedRoles = $this->mergeRolesFromJson((string) ($user['roles_json'] ?? '[]'), $mustHaveRoles);
                    if ($updatedRoles !== null) {
                        $this->model->updateUserRoles((int) $user['id'], $updatedRoles);
                        $user = $this->model->findUserById((int) $user['id']);
                    }
                    $this->referrals->ensureUserReferralCode((int) $user['id']);
                }
            } else {
                $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
                $baseRoles = ['student'];
                $roles = array_values(array_unique(array_merge($baseRoles, $mustHaveRoles)));
                $rolesJson = json_encode($roles);
                if (!is_string($rolesJson) || $rolesJson === '') {
                    $rolesJson = '["student"]';
                }
                $userId = $this->model->createActiveVerifiedUser(
                    $names['first'],
                    $names['last'],
                    $email,
                    $passwordHash,
                    $rolesJson
                );
                $isNewUser = true;
                $this->referrals->ensureUserReferralCode($userId);
                $this->applyReferralFromSession($userId);
                unset($_SESSION['referral_code'], $_SESSION['referral_at'], $_SESSION['referral_course_token']);
                $user = $this->model->findUserById($userId);
            }

            if (!$user) {
                throw new \RuntimeException('user_not_found');
            }

            $this->model->commit();
            $this->loginUser($user, false);

            if ($isNewUser) {
                $this->flashSet('success', "Compte cr\u{00e9}\u{00e9} avec Google.");
            }

            $redirectPath = $this->sanitizeReturnPath($returnPath, $this->siteUrl(''));
            if ($source === 'company_invite' && $inviteToken !== '') {
                $redirectPath = $this->siteUrl('company/invite_accept?token=' . urlencode($inviteToken));
            } elseif ($source === 'company_register') {
                $redirectPath = $this->siteUrl('company/register?google=1');
            }

            return ['redirectTo' => $redirectPath];
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }

            $this->logError('Connexion Google en erreur.', [
                'source' => $source,
                'email' => $email,
                'exception' => $e,
            ]);

            $message = 'Connexion Google impossible.';
            if ($e->getMessage() === 'suspended') {
                $message = 'Compte suspendu. Contactez le support.';
            }
            $this->flashSet('error', $message);
            return ['redirectTo' => $sourcePath];
        }
    }

    private function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    private function normalizeSource(string $source): string
    {
        $allowed = ['login', 'register', 'company_register', 'company_invite'];
        return in_array($source, $allowed, true) ? $source : 'login';
    }

    /**
     * @return array<int,string>
     */
    private function rolesForSource(string $source): array
    {
        if ($source === 'company_register') {
            return ['company_admin'];
        }
        if ($source === 'company_invite') {
            return ['company_member'];
        }
        return [];
    }

    private function sourceEntryPath(string $source, string $inviteToken = ''): string
    {
        if ($source === 'register') {
            return $this->siteUrl('register');
        }
        if ($source === 'company_register') {
            return $this->siteUrl('company/register');
        }
        if ($source === 'company_invite') {
            if ($inviteToken !== '') {
                return $this->siteUrl('company/invite_accept?token=' . urlencode($inviteToken));
            }
            return $this->siteUrl('company/invite_accept');
        }
        return $this->siteUrl('login');
    }

    /**
     * @param array<int,string> $mustHaveRoles
     */
    private function mergeRolesFromJson(string $rolesJson, array $mustHaveRoles): ?string
    {
        $decoded = json_decode($rolesJson, true);
        $roles = is_array($decoded) ? array_values(array_filter($decoded, static fn ($r): bool => is_string($r) && $r !== '')) : [];
        $original = $roles;
        foreach ($mustHaveRoles as $role) {
            if (!in_array($role, $roles, true)) {
                $roles[] = $role;
            }
        }
        if ($roles !== [] && !in_array('student', $roles, true)) {
            $roles[] = 'student';
        }
        $roles = array_values(array_unique($roles));
        if ($roles === $original) {
            return null;
        }
        $json = json_encode($roles);
        return is_string($json) && $json !== '' ? $json : null;
    }

    /**
     * @return array{ok:bool,access_token?:string}
     */
    private function exchangeCode(string $code): array
    {
        if (!function_exists('curl_init')) {
            $this->logError('cURL indisponible pour l echange OAuth Google.');
            return ['ok' => false];
        }

        $payload = http_build_query([
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->absUrl('oauth/google'),
            'grant_type' => 'authorization_code',
        ]);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        if (!$ch) {
            $this->logError('Impossible d initialiser cURL pour l echange OAuth Google.');
            return ['ok' => false];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            $this->logError("Echec HTTP pendant l'echange OAuth Google.", [
                'status' => $status,
                'curl_error' => $error,
                'raw' => $response === false ? null : (string) $response,
            ]);
            return ['ok' => false];
        }

        $json = json_decode((string) $response, true);
        if (!is_array($json) || empty($json['access_token'])) {
            $this->logError("Reponse invalide pendant l'echange OAuth Google.", [
                'status' => $status,
                'raw' => (string) $response,
            ]);
            return ['ok' => false];
        }

        return [
            'ok' => true,
            'access_token' => (string) $json['access_token'],
        ];
    }

    /**
     * @return array{ok:bool,email?:string,email_verified?:bool,given_name?:string,family_name?:string,name?:string}
     */
    private function fetchUserProfile(string $accessToken): array
    {
        if (!function_exists('curl_init')) {
            $this->logError('cURL indisponible pour la lecture du profil Google.');
            return ['ok' => false];
        }

        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        if (!$ch) {
            $this->logError('Impossible d initialiser cURL pour la lecture du profil Google.');
            return ['ok' => false];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            $this->logError('Echec HTTP pendant la lecture du profil Google.', [
                'status' => $status,
                'curl_error' => $error,
                'raw' => $response === false ? null : (string) $response,
            ]);
            return ['ok' => false];
        }

        $json = json_decode((string) $response, true);
        if (!is_array($json)) {
            $this->logError('Reponse JSON invalide pour le profil Google.', [
                'status' => $status,
                'raw' => (string) $response,
            ]);
            return ['ok' => false];
        }

        return [
            'ok' => true,
            'email' => (string) ($json['email'] ?? ''),
            'email_verified' => (bool) ($json['email_verified'] ?? false),
            'given_name' => (string) ($json['given_name'] ?? ''),
            'family_name' => (string) ($json['family_name'] ?? ''),
            'name' => (string) ($json['name'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $profile
     * @return array{first:string,last:string}
     */
    private function resolveNames(array $profile): array
    {
        $first = trim((string) ($profile['given_name'] ?? ''));
        $last = trim((string) ($profile['family_name'] ?? ''));
        if ($first === '' && $last === '') {
            $fullName = trim((string) ($profile['name'] ?? ''));
            if ($fullName !== '') {
                $parts = preg_split('/\s+/', $fullName);
                if (is_array($parts) && $parts !== []) {
                    $first = (string) ($parts[0] ?? '');
                    if (count($parts) > 1) {
                        $last = trim(implode(' ', array_slice($parts, 1)));
                    }
                }
            }
        }

        if ($first === '') {
            $first = 'Utilisateur';
        }
        if ($last === '') {
            $last = 'Google';
        }

        return [
            'first' => substr($first, 0, 100),
            'last' => substr($last, 0, 100),
        ];
    }

    private function applyReferralFromSession(int $newUserId): void
    {
        $referralInput = trim((string) ($_SESSION['referral_code'] ?? ''));
        if ($referralInput === '') {
            return;
        }

        $referralCode = $referralInput;
        $parts = explode('.', $referralInput, 2);
        if (count($parts) === 2) {
            $referralCode = trim((string) $parts[1]);
        }
        if ($referralCode === '') {
            return;
        }

        $referrerId = $this->referrals->getUserIdByReferralCode($referralCode);
        if (!$referrerId || $referrerId === $newUserId) {
            return;
        }

        if (!$this->referrals->userHasActiveEnrollment((int) $referrerId)) {
            return;
        }

        $this->referrals->setUserReferrer($newUserId, (int) $referrerId);
    }
}
