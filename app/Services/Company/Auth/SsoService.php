<?php
declare(strict_types=1);

namespace App\Services\Company\Auth;

use App\Core\Service;
use App\Models\CompanyDashboardModel;
use App\Models\CompanySsoModel;
use App\Services\Shared\CompanySchemaService;
use Throwable;

final class SsoService extends Service
{
    private CompanyDashboardModel $companyModel;
    private CompanySsoModel $authModel;
    private CompanySchemaService $companies;

    public function __construct(
        CompanyDashboardModel $companyModel,
        CompanySsoModel $authModel,
        CompanySchemaService $companies
    ) {
        $this->companyModel = $companyModel;
        $this->authModel = $authModel;
        $this->companies = $companies;
    }

    /**
     * @param array<string,mixed> $query
     * @return array{redirectTo:string}
     */
    public function handle(array $query): array
    {
        $this->companies->ensureDashboardSchema();

        if (isset($query['SAMLResponse'])) {
            return $this->handleSamlCallback($query);
        }

        $isCallback = isset($query['code']) || isset($query['error']) || isset($query['state']);
        return $isCallback ? $this->handleOidcCallback($query) : $this->startFlow($query);
    }

    /**
     * @param array<string,mixed> $query
     * @return array{redirectTo:string}
     */
    private function startFlow(array $query): array
    {
        $returnInput = (string) ($query['return'] ?? '');
        $returnPath = $this->sanitizeReturnPath($returnInput, $this->siteUrl('company/dashboard'));

        $email = strtolower(trim((string) ($query['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flashSet('error', 'Adresse e-mail requise pour démarrer la connexion SSO.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $domain = $this->emailDomain($email);
        if ($domain === '') {
            $this->flashSet('error', 'Domaine d’adresse e-mail invalide.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $config = $this->companyModel->findCompanySsoConfigByDomain($domain);
        if (!$config) {
            $this->flashSet('error', 'Aucun SSO actif n’est configuré pour ce domaine.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $provider = strtolower((string) ($config['provider'] ?? ''));
        if ($provider === 'saml') {
            return $this->startSamlFlow($config, $returnPath, $domain);
        }
        if ($provider !== 'oidc') {
            $this->flashSet('error', 'Le SSO est indisponible pour ce domaine.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        return $this->startOidcFlow($config, $email, $domain, $returnPath);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function startOidcFlow(array $config, string $email, string $domain, string $returnPath): array
    {
        $issuer = trim((string) ($config['issuer'] ?? ''));
        $clientId = trim((string) ($config['client_id'] ?? ''));
        $clientSecret = $this->decryptSecret((string) ($config['client_secret_enc'] ?? ''));
        if ($issuer === '' || $clientId === '' || $clientSecret === '') {
            $this->flashSet('error', 'Configuration OIDC incomplète.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $discovery = $this->discoverOidcEndpoints($issuer);
        if (!$discovery || ($discovery['token_endpoint'] ?? '') === '') {
            $this->flashSet('error', 'Impossible de récupérer la configuration OIDC.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $authEndpoint = trim((string) ($config['entry_point'] ?? ''));
        if ($authEndpoint === '') {
            $authEndpoint = (string) ($discovery['authorization_endpoint'] ?? '');
        }
        if ($authEndpoint === '') {
            $this->flashSet('error', 'Point de connexion OIDC manquant.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $state = $this->randomState();
        $_SESSION['company_sso_state'] = $state;
        $_SESSION['company_sso_company_id'] = (int) ($config['company_id'] ?? 0);
        $_SESSION['company_sso_return'] = $returnPath;
        $_SESSION['company_sso_domain'] = $domain;
        $_SESSION['company_sso_provider'] = 'oidc';
        $_SESSION['company_sso_token_endpoint'] = (string) ($discovery['token_endpoint'] ?? '');
        $_SESSION['company_sso_userinfo_endpoint'] = (string) ($discovery['userinfo_endpoint'] ?? '');
        $_SESSION['company_sso_issuer'] = $issuer;

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $this->absUrl('company/sso'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'login_hint' => $email,
        ];

        return ['redirectTo' => $authEndpoint . '?' . http_build_query($params)];
    }

    /**
     * @param array<string,mixed> $config
     */
    private function startSamlFlow(array $config, string $returnPath, string $domain): array
    {
        $entryPoint = trim((string) ($config['entry_point'] ?? ''));
        $certPem = trim((string) ($config['cert_pem'] ?? ''));
        if ($entryPoint === '' || $certPem === '') {
            $this->flashSet('error', 'Configuration SAML incomplète.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $companyId = (int) ($config['company_id'] ?? 0);
        if ($companyId < 1) {
            $this->flashSet('error', 'Connexion SSO invalide.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $requestId = $this->randomRequestId();
        $_SESSION['company_sso_saml_request_id'] = $requestId;
        $_SESSION['company_sso_saml_company_id'] = $companyId;
        $_SESSION['company_sso_saml_return'] = $returnPath;
        $_SESSION['company_sso_saml_domain'] = $domain;
        $_SESSION['company_sso_saml_issuer'] = trim((string) ($config['issuer'] ?? ''));

        $acsUrl = $this->absUrl('company/sso');
        $entityId = $this->absUrl('company/sso');
        $authnRequest = $this->buildSamlAuthnRequest($entityId, $acsUrl, $requestId, $entryPoint);
        $encoded = $this->encodeSamlRequest($authnRequest);
        if ($encoded === '') {
            $this->flashSet('error', 'Connexion SSO impossible.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $separator = str_contains($entryPoint, '?') ? '&' : '?';
        $redirect = $entryPoint
            . $separator
            . 'SAMLRequest=' . urlencode($encoded)
            . '&RelayState=' . urlencode($returnPath);

        return ['redirectTo' => $redirect];
    }

    /**
     * @param array<string,mixed> $query
     * @return array{redirectTo:string}
     */
    private function handleOidcCallback(array $query): array
    {
        $returnPath = (string) ($_SESSION['company_sso_return'] ?? $this->siteUrl('company/dashboard'));
        $expectedState = (string) ($_SESSION['company_sso_state'] ?? '');
        $companyId = (int) ($_SESSION['company_sso_company_id'] ?? 0);
        $domain = (string) ($_SESSION['company_sso_domain'] ?? '');
        $tokenEndpoint = (string) ($_SESSION['company_sso_token_endpoint'] ?? '');
        $userinfoEndpoint = (string) ($_SESSION['company_sso_userinfo_endpoint'] ?? '');
        $issuer = (string) ($_SESSION['company_sso_issuer'] ?? '');

        unset(
            $_SESSION['company_sso_state'],
            $_SESSION['company_sso_company_id'],
            $_SESSION['company_sso_return'],
            $_SESSION['company_sso_domain'],
            $_SESSION['company_sso_provider'],
            $_SESSION['company_sso_token_endpoint'],
            $_SESSION['company_sso_userinfo_endpoint'],
            $_SESSION['company_sso_issuer']
        );

        if (!empty($query['error'])) {
            $error = (string) $query['error'];
            $this->flashSet('error', 'Connexion SSO annulée : ' . $error . '.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $state = (string) ($query['state'] ?? '');
        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            $this->flashSet('error', 'Connexion SSO invalide : état OAuth incorrect.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        if ($companyId < 1) {
            $this->flashSet('error', 'Connexion SSO invalide : entreprise introuvable.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $config = $this->companyModel->findCompanySsoConfig($companyId);
        if (!$config || empty($config['is_enabled'])) {
            $this->flashSet('error', 'Connexion SSO indisponible.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $provider = strtolower((string) ($config['provider'] ?? ''));
        if ($provider !== 'oidc') {
            $this->flashSet('error', 'Connexion SSO indisponible.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $clientId = trim((string) ($config['client_id'] ?? ''));
        $clientSecret = $this->decryptSecret((string) ($config['client_secret_enc'] ?? ''));
        $issuer = $issuer !== '' ? $issuer : trim((string) ($config['issuer'] ?? ''));
        if ($clientId === '' || $clientSecret === '') {
            $this->flashSet('error', 'Connexion SSO impossible : configuration incomplète.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        if ($tokenEndpoint === '' && $issuer !== '') {
            $discovery = $this->discoverOidcEndpoints($issuer);
            $tokenEndpoint = (string) ($discovery['token_endpoint'] ?? '');
            $userinfoEndpoint = (string) ($discovery['userinfo_endpoint'] ?? '');
        }
        if ($tokenEndpoint === '') {
            $this->flashSet('error', 'Connexion SSO impossible : point de terminaison du jeton manquant.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $code = (string) ($query['code'] ?? '');
        if ($code === '') {
            $this->flashSet('error', 'Connexion SSO invalide : code OAuth manquant.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $exchange = $this->exchangeCode($tokenEndpoint, $clientId, $clientSecret, $code);
        if (empty($exchange['ok'])) {
            $this->flashSet('error', 'Connexion SSO impossible : échec de l’échange du jeton.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $accessToken = (string) ($exchange['access_token'] ?? '');
        $idToken = (string) ($exchange['id_token'] ?? '');

        $profile = [
            'ok' => false,
        ];
        if ($accessToken !== '' && $userinfoEndpoint !== '') {
            $profile = $this->fetchUserProfile($userinfoEndpoint, $accessToken);
        }
        if (empty($profile['ok']) && $idToken !== '') {
            $profile = $this->profileFromIdToken($idToken);
        }
        if (empty($profile['ok'])) {
            $this->flashSet('error', 'Connexion SSO impossible : profil utilisateur indisponible.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        $emailVerified = !empty($profile['email_verified']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$emailVerified) {
            $this->flashSet('error', 'Connexion SSO refusée : adresse e-mail non vérifiée.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        if ($domain !== '' && $this->emailDomain($email) !== $domain) {
            $this->flashSet('error', 'Connexion SSO refusée : domaine non autorisé.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        return $this->completeLogin($companyId, $email, $profile, 'oidc', $returnPath);
    }

    /**
     * @param array<string,mixed> $query
     * @return array{redirectTo:string}
     */
    private function handleSamlCallback(array $query): array
    {
        $returnPath = (string) ($_SESSION['company_sso_saml_return'] ?? $this->siteUrl('company/dashboard'));
        $companyId = (int) ($_SESSION['company_sso_saml_company_id'] ?? 0);
        $expectedRequestId = (string) ($_SESSION['company_sso_saml_request_id'] ?? '');
        $domain = (string) ($_SESSION['company_sso_saml_domain'] ?? '');
        $issuerExpected = (string) ($_SESSION['company_sso_saml_issuer'] ?? '');

        unset(
            $_SESSION['company_sso_saml_return'],
            $_SESSION['company_sso_saml_company_id'],
            $_SESSION['company_sso_saml_request_id'],
            $_SESSION['company_sso_saml_domain'],
            $_SESSION['company_sso_saml_issuer']
        );

        if ($companyId < 1) {
            $this->flashSet('error', 'Connexion SSO invalide : session expirée.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $encoded = (string) ($query['SAMLResponse'] ?? '');
        if ($encoded === '') {
            $this->flashSet('error', 'Connexion SSO invalide : réponse manquante.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $xml = base64_decode($encoded, true);
        if ($xml === false || $xml === '') {
            $this->flashSet('error', 'Connexion SSO invalide : réponse illisible.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $parsed = $this->parseSamlResponse($xml);
        if (!$parsed) {
            $this->flashSet('error', 'Connexion SSO invalide : format SAML incorrect.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        if ($expectedRequestId !== '' && $parsed['in_response_to'] !== '' && $parsed['in_response_to'] !== $expectedRequestId) {
            $this->flashSet('error', 'Connexion SSO invalide : requête SAML non reconnue.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $destination = $parsed['destination'];
        $acsUrl = $this->absUrl('company/sso');
        if ($destination !== '' && !str_starts_with($destination, $acsUrl)) {
            $this->flashSet('error', 'Connexion SSO invalide : destination non autorisée.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        if ($issuerExpected !== '' && $parsed['issuer'] !== '' && $parsed['issuer'] !== $issuerExpected) {
            $this->flashSet('error', 'Connexion SSO invalide : émetteur incorrect.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $config = $this->companyModel->findCompanySsoConfig($companyId);
        if (!$config || empty($config['is_enabled'])) {
            $this->flashSet('error', 'Connexion SSO indisponible.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        if (strtolower((string) ($config['provider'] ?? '')) !== 'saml') {
            $this->flashSet('error', 'Connexion SSO indisponible.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $certPem = trim((string) ($config['cert_pem'] ?? ''));
        if ($certPem === '') {
            $this->flashSet('error', 'Connexion SSO impossible : certificat manquant.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        if (!$this->isSamlStatusSuccess($parsed['status'])) {
            $this->flashSet('error', 'Connexion SSO refusée.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        if (!$this->verifySamlSignature($parsed['doc'], $parsed['signature'], $parsed['signed_element'], $certPem)) {
            $this->flashSet('error', 'Connexion SSO invalide : signature incorrecte.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        if (!$this->isSamlConditionsValid($parsed['assertion'], $this->absUrl('company/sso'))) {
            $this->flashSet('error', 'Connexion SSO invalide : assertion expirée.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $email = $this->resolveSamlEmail($parsed['name_id'], $parsed['attributes']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flashSet('error', 'Connexion SSO invalide : adresse e-mail manquante.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        if ($domain !== '' && $this->emailDomain($email) !== $domain) {
            $this->flashSet('error', 'Connexion SSO refusée : domaine non autorisé.');
            return ['redirectTo' => $this->siteUrl('login')];
        }

        $profile = $this->samlProfileFromAttributes($email, $parsed['attributes']);
        return $this->completeLogin($companyId, $email, $profile, 'saml', $returnPath);
    }

    /**
     * @param array<string,mixed> $profile
     * @return array{redirectTo:string}
     */
    private function completeLogin(int $companyId, string $email, array $profile, string $provider, string $returnPath): array
    {
        try {
            $this->authModel->beginTransaction();

            $user = $this->authModel->findUserByEmail($email);
            $isNewUser = false;
            $requiredRoles = ['company_member'];

            if ($user) {
                $status = (string) ($user['status'] ?? '');
                if ($status === 'suspended') {
                    throw new \RuntimeException('suspended');
                }
                if ($status !== 'active' || empty($user['email_verified_at'])) {
                    $this->authModel->activateAndVerifyUser((int) $user['id']);
                    $user = $this->authModel->findUserById((int) $user['id']);
                }
                if ($user) {
                    $updatedRoles = $this->mergeRolesFromJson((string) ($user['roles_json'] ?? '[]'), $requiredRoles);
                    if ($updatedRoles !== null) {
                        $this->authModel->updateUserRoles((int) $user['id'], $updatedRoles);
                        $user = $this->authModel->findUserById((int) $user['id']);
                    }
                }
            } else {
                $names = $this->resolveNames($profile);
                $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
                $roles = array_values(array_unique(array_merge(['student'], $requiredRoles)));
                $rolesJson = json_encode($roles);
                if (!is_string($rolesJson) || $rolesJson === '') {
                    $rolesJson = '["student"]';
                }
                $userId = $this->authModel->createActiveVerifiedUser(
                    $names['first'],
                    $names['last'],
                    $email,
                    $passwordHash,
                    $rolesJson
                );
                $user = $this->authModel->findUserById($userId);
                $isNewUser = true;
            }

            if (!$user) {
                throw new \RuntimeException('user_not_found');
            }

            $member = $this->companyModel->findCompanyMemberByUserId($companyId, (int) $user['id']);
            if ($member && (string) ($member['status'] ?? '') !== 'active') {
                throw new \RuntimeException('member_blocked');
            }
            if (!$member) {
                throw new \RuntimeException('invitation_required');
            }

            $this->authModel->commit();
            $this->loginUser($user, false);

            try {
                $this->companyModel->addAuditEvent((int) $user['id'], 'sso_login', 'company', $companyId, [
                    'provider' => $provider,
                    'email' => $email,
                    'created' => $isNewUser ? 1 : 0,
                ]);
            } catch (Throwable $e) {
            }

            $redirectPath = $this->sanitizeReturnPath($returnPath, $this->siteUrl('company/dashboard'));
            return ['redirectTo' => $redirectPath];
        } catch (\Throwable $e) {
            if ($this->authModel->inTransaction()) {
                $this->authModel->rollBack();
            }
            $message = 'Connexion SSO impossible.';
            if ($e->getMessage() === 'suspended') {
                $message = 'Compte suspendu. Contactez le support.';
            } elseif ($e->getMessage() === 'invitation_required') {
                $message = 'Accès entreprise refusé. Une invitation active est requise.';
            } elseif ($e->getMessage() === 'member_blocked') {
                $message = 'Accès entreprise bloqué. Contactez un administrateur.';
            }
            $this->flashSet('error', $message);
            return ['redirectTo' => $this->siteUrl('login')];
        }
    }

    private function randomState(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Throwable $e) {
            return bin2hex((string) microtime(true));
        }
    }

    private function randomRequestId(): string
    {
        return '_' . $this->randomState();
    }

    private function emailDomain(string $email): string
    {
        $parts = explode('@', $email, 2);
        return strtolower(trim((string) ($parts[1] ?? '')));
    }

    /**
     * @return array{authorization_endpoint?:string,token_endpoint?:string,userinfo_endpoint?:string}|null
     */
    private function discoverOidcEndpoints(string $issuer): ?array
    {
        $issuer = trim($issuer);
        if ($issuer === '' || !filter_var($issuer, FILTER_VALIDATE_URL) || !function_exists('curl_init')) {
            return null;
        }
        $url = rtrim($issuer, '/') . '/.well-known/openid-configuration';
        $ch = curl_init($url);
        if (!$ch) {
            return null;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            return null;
        }

        $json = json_decode((string) $response, true);
        if (!is_array($json)) {
            return null;
        }

        return [
            'authorization_endpoint' => (string) ($json['authorization_endpoint'] ?? ''),
            'token_endpoint' => (string) ($json['token_endpoint'] ?? ''),
            'userinfo_endpoint' => (string) ($json['userinfo_endpoint'] ?? ''),
        ];
    }

    /**
     * @return array{ok:bool,access_token?:string,id_token?:string}
     */
    private function exchangeCode(string $tokenEndpoint, string $clientId, string $clientSecret, string $code): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false];
        }

        $payload = http_build_query([
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $this->absUrl('company/sso'),
            'grant_type' => 'authorization_code',
        ]);

        $ch = curl_init($tokenEndpoint);
        if (!$ch) {
            return ['ok' => false];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            return ['ok' => false];
        }

        $json = json_decode((string) $response, true);
        if (!is_array($json)) {
            return ['ok' => false];
        }

        $accessToken = (string) ($json['access_token'] ?? '');
        $idToken = (string) ($json['id_token'] ?? '');
        if ($accessToken === '' && $idToken === '') {
            return ['ok' => false];
        }

        return [
            'ok' => true,
            'access_token' => $accessToken,
            'id_token' => $idToken,
        ];
    }

    /**
     * @return array{ok:bool,email?:string,email_verified?:bool,given_name?:string,family_name?:string,name?:string}
     */
    private function fetchUserProfile(string $userinfoEndpoint, string $accessToken): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false];
        }

        $ch = curl_init($userinfoEndpoint);
        if (!$ch) {
            return ['ok' => false];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            return ['ok' => false];
        }

        $json = json_decode((string) $response, true);
        if (!is_array($json)) {
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
     * @return array{ok:bool,email?:string,email_verified?:bool,given_name?:string,family_name?:string,name?:string}
     */
    private function profileFromIdToken(string $idToken): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) < 2) {
            return ['ok' => false];
        }
        $payload = $this->base64UrlDecode($parts[1]);
        if ($payload === null) {
            return ['ok' => false];
        }
        $json = json_decode($payload, true);
        if (!is_array($json)) {
            return ['ok' => false];
        }

        return [
            'ok' => true,
            'email' => (string) ($json['email'] ?? ''),
            'email_verified' => !empty($json['email_verified']),
            'given_name' => (string) ($json['given_name'] ?? ''),
            'family_name' => (string) ($json['family_name'] ?? ''),
            'name' => (string) ($json['name'] ?? ''),
        ];
    }

    private function base64UrlDecode(string $encoded): ?string
    {
        $encoded = strtr($encoded, '-_', '+/');
        $padding = strlen($encoded) % 4;
        if ($padding > 0) {
            $encoded .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            return null;
        }
        return $decoded;
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
            $last = 'SSO';
        }

        return [
            'first' => substr($first, 0, 100),
            'last' => substr($last, 0, 100),
        ];
    }

    private function buildSamlAuthnRequest(string $entityId, string $acsUrl, string $requestId, string $destination): string
    {
        $issueInstant = gmdate('Y-m-d\TH:i:s\Z');
        $safeEntityId = htmlspecialchars($entityId, ENT_QUOTES, 'UTF-8');
        $safeAcs = htmlspecialchars($acsUrl, ENT_QUOTES, 'UTF-8');
        $safeDest = htmlspecialchars($destination, ENT_QUOTES, 'UTF-8');
        $safeId = htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"'
            . ' ID="' . $safeId . '" Version="2.0" IssueInstant="' . $issueInstant . '"'
            . ' Destination="' . $safeDest . '"'
            . ' AssertionConsumerServiceURL="' . $safeAcs . '"'
            . ' ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">'
            . '<saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $safeEntityId . '</saml:Issuer>'
            . '<samlp:NameIDPolicy AllowCreate="true" Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress" />'
            . '</samlp:AuthnRequest>';
    }

    private function encodeSamlRequest(string $xml): string
    {
        if ($xml === '') {
            return '';
        }
        if (function_exists('gzdeflate')) {
            $deflated = gzdeflate($xml);
            if ($deflated !== false) {
                return base64_encode($deflated);
            }
        }
        return base64_encode($xml);
    }

    /**
     * @return array{
     *  doc:\DOMDocument,
     *  response:\DOMElement,
     *  assertion:\DOMElement,
     *  signature:\DOMElement,
     *  signed_element:\DOMElement,
     *  issuer:string,
     *  status:string,
     *  in_response_to:string,
     *  destination:string,
     *  name_id:string,
     *  attributes:array<string,array<int,string>>
     * }|null
     */
    private function parseSamlResponse(string $xml): ?array
    {
        $internalErrors = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        $loaded = $doc->loadXML($xml, LIBXML_NONET);
        if (!$loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);
            return null;
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
        $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $response = $xpath->query('/samlp:Response')->item(0);
        $assertion = $xpath->query('/samlp:Response/saml:Assertion')->item(0);
        if (!$response instanceof \DOMElement || !$assertion instanceof \DOMElement) {
            libxml_use_internal_errors($internalErrors);
            return null;
        }

        $signature = $xpath->query('/samlp:Response/ds:Signature')->item(0);
        $signedElement = $response;
        if (!$signature instanceof \DOMElement) {
            $signature = $xpath->query('/samlp:Response/saml:Assertion/ds:Signature')->item(0);
            $signedElement = $assertion;
        }
        if (!$signature instanceof \DOMElement) {
            libxml_use_internal_errors($internalErrors);
            return null;
        }

        $issuer = (string) $xpath->evaluate('string(/samlp:Response/saml:Issuer)');
        $status = (string) $xpath->evaluate('string(/samlp:Response/samlp:Status/samlp:StatusCode/@Value)');
        $nameId = (string) $xpath->evaluate('string(/samlp:Response/saml:Assertion/saml:Subject/saml:NameID)');

        $attributes = [];
        $attrNodes = $xpath->query('/samlp:Response/saml:Assertion/saml:AttributeStatement/saml:Attribute');
        if ($attrNodes) {
            foreach ($attrNodes as $attr) {
                if (!$attr instanceof \DOMElement) {
                    continue;
                }
                $name = trim((string) $attr->getAttribute('Name'));
                if ($name === '') {
                    continue;
                }
                $values = [];
                $valueNodes = $xpath->query('saml:AttributeValue', $attr);
                if ($valueNodes) {
                    foreach ($valueNodes as $valueNode) {
                        if (!$valueNode instanceof \DOMElement) {
                            continue;
                        }
                        $value = trim((string) $valueNode->textContent);
                        if ($value !== '') {
                            $values[] = $value;
                        }
                    }
                }
                if ($values !== []) {
                    $attributes[$name] = $values;
                }
            }
        }

        libxml_use_internal_errors($internalErrors);

        return [
            'doc' => $doc,
            'response' => $response,
            'assertion' => $assertion,
            'signature' => $signature,
            'signed_element' => $signedElement,
            'issuer' => $issuer,
            'status' => $status,
            'in_response_to' => (string) $response->getAttribute('InResponseTo'),
            'destination' => (string) $response->getAttribute('Destination'),
            'name_id' => $nameId,
            'attributes' => $attributes,
        ];
    }

    private function isSamlStatusSuccess(string $status): bool
    {
        return $status === 'urn:oasis:names:tc:SAML:2.0:status:Success';
    }

    private function isSamlConditionsValid(\DOMElement $assertion, string $entityId): bool
    {
        $xpath = new \DOMXPath($assertion->ownerDocument);
        $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

        $conditions = $xpath->query('saml:Conditions', $assertion)->item(0);
        if ($conditions instanceof \DOMElement) {
            $now = time();
            $skew = 300;
            $notBefore = trim((string) $conditions->getAttribute('NotBefore'));
            if ($notBefore !== '') {
                $nb = strtotime($notBefore);
                if ($nb !== false && ($nb - $skew) > $now) {
                    return false;
                }
            }
            $notOnOrAfter = trim((string) $conditions->getAttribute('NotOnOrAfter'));
            if ($notOnOrAfter !== '') {
                $noa = strtotime($notOnOrAfter);
                if ($noa !== false && ($noa + $skew) <= $now) {
                    return false;
                }
            }

            $audiences = $xpath->query('saml:AudienceRestriction/saml:Audience', $conditions);
            if ($audiences && $audiences->length > 0) {
                $ok = false;
                foreach ($audiences as $audienceNode) {
                    if (!$audienceNode instanceof \DOMElement) {
                        continue;
                    }
                    $aud = trim((string) $audienceNode->textContent);
                    if ($aud !== '' && $aud === $entityId) {
                        $ok = true;
                        break;
                    }
                }
                if (!$ok) {
                    return false;
                }
            }
        }

        $subjectDataNodes = $xpath->query('saml:Subject/saml:SubjectConfirmation/saml:SubjectConfirmationData', $assertion);
        if ($subjectDataNodes) {
            foreach ($subjectDataNodes as $dataNode) {
                if (!$dataNode instanceof \DOMElement) {
                    continue;
                }
                $recipient = trim((string) $dataNode->getAttribute('Recipient'));
                if ($recipient !== '' && !str_starts_with($recipient, $entityId)) {
                    return false;
                }
                $notOnOrAfter = trim((string) $dataNode->getAttribute('NotOnOrAfter'));
                if ($notOnOrAfter !== '') {
                    $noa = strtotime($notOnOrAfter);
                    if ($noa !== false && $noa <= time()) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param array<string,array<int,string>> $attributes
     */
    private function resolveSamlEmail(string $nameId, array $attributes): string
    {
        $candidate = trim($nameId);
        if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            return strtolower($candidate);
        }

        $keys = [
            'email',
            'mail',
            'EmailAddress',
            'User.email',
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
        ];

        foreach ($keys as $key) {
            $value = $this->firstAttributeValue($attributes, [$key]);
            if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return strtolower($value);
            }
        }

        foreach ($attributes as $name => $values) {
            $nameLower = strtolower($name);
            if (!str_contains($nameLower, 'mail')) {
                continue;
            }
            $value = (string) ($values[0] ?? '');
            if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return strtolower($value);
            }
        }

        return '';
    }

    /**
     * @param array<string,array<int,string>> $attributes
     * @return array<string,mixed>
     */
    private function samlProfileFromAttributes(string $email, array $attributes): array
    {
        $first = $this->firstAttributeValue($attributes, [
            'given_name',
            'givenName',
            'first_name',
            'firstname',
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
        ]);
        $last = $this->firstAttributeValue($attributes, [
            'family_name',
            'familyName',
            'last_name',
            'surname',
            'sn',
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
        ]);
        $display = $this->firstAttributeValue($attributes, [
            'displayName',
            'name',
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name',
        ]);

        return [
            'ok' => true,
            'email' => $email,
            'email_verified' => true,
            'given_name' => $first,
            'family_name' => $last,
            'name' => $display,
        ];
    }

    /**
     * @param array<string,array<int,string>> $attributes
     * @param array<int,string> $keys
     */
    private function firstAttributeValue(array $attributes, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($attributes[$key]) && $attributes[$key] !== []) {
                $value = trim((string) ($attributes[$key][0] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        $lower = array_change_key_case($attributes, CASE_LOWER);
        foreach ($keys as $key) {
            $keyLower = strtolower($key);
            if (isset($lower[$keyLower]) && $lower[$keyLower] !== []) {
                $value = trim((string) ($lower[$keyLower][0] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private function verifySamlSignature(\DOMDocument $doc, \DOMElement $signatureNode, \DOMElement $signedElement, string $certPem): bool
    {
        if (!function_exists('openssl_verify')) {
            return false;
        }

        $cert = $this->normalizeCertPem($certPem);
        if ($cert === '') {
            return false;
        }

        $publicKey = openssl_pkey_get_public($cert);
        if (!$publicKey) {
            return false;
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $signedInfo = $xpath->query('ds:SignedInfo', $signatureNode)->item(0);
        if (!$signedInfo instanceof \DOMElement) {
            return false;
        }

        $signatureValue = base64_decode(trim((string) $xpath->evaluate('string(ds:SignatureValue)', $signatureNode)), true);
        if ($signatureValue === false) {
            return false;
        }

        $c14nAlg = (string) $xpath->evaluate('string(ds:SignedInfo/ds:CanonicalizationMethod/@Algorithm)', $signatureNode);
        $exclusive = str_contains($c14nAlg, 'exc-c14n');
        $signedInfoC14n = $signedInfo->C14N($exclusive, false);
        if ($signedInfoC14n === false) {
            return false;
        }

        $sigAlg = (string) $xpath->evaluate('string(ds:SignedInfo/ds:SignatureMethod/@Algorithm)', $signatureNode);
        $opensslAlg = $this->mapSignatureAlgorithm($sigAlg);
        if ($opensslAlg === null) {
            return false;
        }

        if (openssl_verify($signedInfoC14n, $signatureValue, $publicKey, $opensslAlg) !== 1) {
            return false;
        }

        $reference = $xpath->query('ds:SignedInfo/ds:Reference', $signatureNode)->item(0);
        if (!$reference instanceof \DOMElement) {
            return false;
        }

        $referenceUri = ltrim((string) $reference->getAttribute('URI'), '#');
        $signedId = $this->resolveElementId($signedElement);
        if ($referenceUri === '' || $signedId === '' || $referenceUri !== $signedId) {
            return false;
        }

        $transforms = $xpath->query('ds:Transforms/ds:Transform', $reference);
        $removeSignature = false;
        $exclusiveDigest = false;
        if ($transforms) {
            foreach ($transforms as $transform) {
                if (!$transform instanceof \DOMElement) {
                    continue;
                }
                $algo = (string) $transform->getAttribute('Algorithm');
                if (str_contains($algo, 'enveloped-signature')) {
                    $removeSignature = true;
                }
                if (str_contains($algo, 'exc-c14n')) {
                    $exclusiveDigest = true;
                }
            }
        }

        $digestMethod = (string) $xpath->evaluate('string(ds:DigestMethod/@Algorithm)', $reference);
        $hashAlgo = $this->mapDigestAlgorithm($digestMethod);
        if ($hashAlgo === null) {
            return false;
        }

        $expectedDigest = base64_decode(trim((string) $xpath->evaluate('string(ds:DigestValue)', $reference)), true);
        if ($expectedDigest === false) {
            return false;
        }

        $cloneDoc = new \DOMDocument();
        $cloneDoc->preserveWhiteSpace = false;
        $cloneDoc->formatOutput = false;
        $cloneNode = $cloneDoc->importNode($signedElement, true);
        $cloneDoc->appendChild($cloneNode);

        if ($removeSignature) {
            $cloneXpath = new \DOMXPath($cloneDoc);
            $cloneXpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
            $sigNodes = $cloneXpath->query('//ds:Signature', $cloneNode);
            if ($sigNodes) {
                foreach ($sigNodes as $sigNode) {
                    if ($sigNode instanceof \DOMElement && $sigNode->parentNode) {
                        $sigNode->parentNode->removeChild($sigNode);
                    }
                }
            }
        }

        $canonical = $cloneNode->C14N($exclusiveDigest, false);
        if ($canonical === false) {
            return false;
        }

        $actualDigest = hash($hashAlgo, $canonical, true);
        if (!hash_equals($expectedDigest, $actualDigest)) {
            return false;
        }

        return true;
    }

    private function mapSignatureAlgorithm(string $uri): ?int
    {
        return match ($uri) {
            'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256' => OPENSSL_ALGO_SHA256,
            'http://www.w3.org/2000/09/xmldsig#rsa-sha1' => OPENSSL_ALGO_SHA1,
            'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512' => OPENSSL_ALGO_SHA512,
            default => null,
        };
    }

    private function mapDigestAlgorithm(string $uri): ?string
    {
        return match ($uri) {
            'http://www.w3.org/2001/04/xmlenc#sha256' => 'sha256',
            'http://www.w3.org/2000/09/xmldsig#sha1' => 'sha1',
            'http://www.w3.org/2001/04/xmlenc#sha512' => 'sha512',
            default => null,
        };
    }

    private function resolveElementId(\DOMElement $element): string
    {
        foreach (['ID', 'Id', 'id'] as $attr) {
            $value = trim((string) $element->getAttribute($attr));
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }

    private function normalizeCertPem(string $certPem): string
    {
        $cert = trim($certPem);
        if ($cert === '') {
            return '';
        }
        if (str_contains($cert, 'BEGIN CERTIFICATE')) {
            return $cert;
        }
        $clean = preg_replace('/\s+/', '', $cert);
        if (!is_string($clean) || $clean === '') {
            return '';
        }
        return "-----BEGIN CERTIFICATE-----\n"
            . chunk_split($clean, 64, "\n")
            . "-----END CERTIFICATE-----";
    }

    /**
     * @param array<int,string> $mustHaveRoles
     */
    private function mergeRolesFromJson(string $rolesJson, array $mustHaveRoles): ?string
    {
        $decoded = json_decode($rolesJson, true);
        $roles = is_array($decoded) ? array_values(array_filter($decoded, static fn($r): bool => is_string($r) && $r !== '')) : [];
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

    private function decryptSecret(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (!function_exists('openssl_decrypt')) {
            return $value;
        }
        $appKey = $this->appKey();
        if ($appKey === '') {
            return $value;
        }

        $raw = base64_decode($value, true);
        if ($raw === false || strlen($raw) < 17) {
            return $value;
        }

        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $key = hash('sha256', $appKey, true);
        $plain = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $plain !== false ? $plain : $value;
    }

    private function appKey(): string
    {
        if (\defined('APP_KEY')) {
            $appKey = trim((string) \constant('APP_KEY'));
            if ($appKey !== '') {
                return $appKey;
            }
        }
        return '';
    }
}
