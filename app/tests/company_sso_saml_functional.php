<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);
require_once $rootDir . '/app/bootstrap.php';

/**
 * @param array<int,string> $errors
 */
function assert_true(bool $condition, string $message, array &$errors): void
{
    if (!$condition) {
        $errors[] = $message;
    }
}

$errors = [];
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec('CREATE TABLE companies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    status TEXT
)');
$pdo->exec('CREATE TABLE company_sso_configs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    provider TEXT,
    domain TEXT,
    issuer TEXT,
    entry_point TEXT,
    cert_pem TEXT,
    client_id TEXT,
    client_secret_enc TEXT,
    is_enabled INTEGER DEFAULT 0,
    created_at TEXT,
    updated_at TEXT
)');

$pdo->exec("INSERT INTO companies (id, name, status) VALUES (10, 'Acme Corp', 'active')");
$pdo->exec("INSERT INTO company_sso_configs (company_id, provider, domain, issuer, entry_point, cert_pem, is_enabled, created_at, updated_at) VALUES
    (10, 'saml', 'acme.test', 'https://idp.acme.test', 'https://idp.acme.test/sso', '-----BEGIN CERTIFICATE-----\nFAKE\n-----END CERTIFICATE-----', 1, datetime('now'), datetime('now'))");

$_SESSION = [];

$service = new \App\Services\Company\Auth\SsoService(
    new \App\Models\CompanyDashboardModel($pdo),
    new \App\Models\CompanySsoModel($pdo),
    new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo))
);

$result = $service->handle([
    'email' => 'user@acme.test',
    'return' => '/company/dashboard',
]);

$redirect = (string) ($result['redirectTo'] ?? '');
assert_true(str_contains($redirect, 'https://idp.acme.test/sso'), 'SAML redirect should use IdP entry point.', $errors);
assert_true(str_contains($redirect, 'SAMLRequest='), 'SAMLRequest parameter should be present.', $errors);
assert_true(str_contains($redirect, 'RelayState='), 'RelayState parameter should be present.', $errors);

assert_true(!empty($_SESSION['company_sso_saml_request_id']), 'SAML request ID should be stored.', $errors);
assert_true((int) ($_SESSION['company_sso_saml_company_id'] ?? 0) === 10, 'Company ID should be stored in session.', $errors);
assert_true((string) ($_SESSION['company_sso_saml_domain'] ?? '') === 'acme.test', 'SSO domain should be stored.', $errors);

if ($errors !== []) {
    echo "Company SSO SAML functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Company SSO SAML functional OK\n";
exit(0);
