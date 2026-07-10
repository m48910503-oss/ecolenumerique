<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;
use App\Models\AdminSiteSettingsModel;

final class SiteSettingsService extends Service
{
    private const DEFAULT_MAINTENANCE_MESSAGE = 'Maintenance temporaire en cours. Merci de revenir dans quelques minutes.';

    private AdminSiteSettingsModel $model;

    public function __construct(AdminSiteSettingsModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(string $method, array $post, array $files): array
    {
        $errors = [];
        $success = (string) $this->flashGet('success', '');

        $siteName = $this->model->getSetting('site_name', defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique');
        $siteTagline = $this->model->getSetting('site_tagline', 'Formations tech par des experts.');
        $siteDescription = $this->model->getSetting('site_description', '');
        $supportEmail = $this->model->getSetting('support_email', defined('APP_FROM_EMAIL') ? (string) APP_FROM_EMAIL : '');
        $supportPhone = $this->model->getSetting('support_phone', '');
        $supportWhatsapp = $this->model->getSetting('support_whatsapp', '');
        $siteAddress = $this->model->getSetting('site_address', '');

        $seoTitle = $this->model->getSetting('seo_title', $siteName);
        $seoDescription = $this->model->getSetting('seo_description', $siteDescription);
        $seoKeywords = $this->model->getSetting('seo_keywords', '');

        $maintenanceEnabled = $this->model->getSetting('maintenance_enabled', '0') === '1';
        $maintenanceMessage = $this->model->getSetting('maintenance_message', self::DEFAULT_MAINTENANCE_MESSAGE);
        $registrationsEnabled = $this->model->getSetting('registrations_enabled', '1') !== '0';
        $teacherApplicationsEnabled = $this->model->getSetting('teacher_applications_enabled', '1') !== '0';
        $fedapayEnabled = $this->model->getSetting('payment_fedapay_enabled', '1') !== '0';
        $fedapayPublicKey = $this->settingWithFallback(
            'payment_fedapay_public_key',
            defined('PAY_FEDAPAY_PUBLIC_KEY') ? (string) PAY_FEDAPAY_PUBLIC_KEY : ''
        );
        $fedapaySecretKey = $this->settingWithFallback(
            'payment_fedapay_secret_key',
            defined('PAY_FEDAPAY_SECRET_KEY') ? (string) PAY_FEDAPAY_SECRET_KEY : ''
        );
        $fedapayEnvironment = $this->normalizeFedapayEnvironment($this->settingWithFallback(
            'payment_fedapay_environment',
            defined('PAY_FEDAPAY_ENV') ? (string) PAY_FEDAPAY_ENV : 'sandbox'
        ));
        $fedapayWebhookSecret = $this->settingWithFallback(
            'payment_fedapay_webhook_secret',
            defined('PAY_FEDAPAY_WEBHOOK_SECRET') ? (string) PAY_FEDAPAY_WEBHOOK_SECRET : ''
        );

        $siteLogoPath = $this->model->getSetting('site_logo_path', '');
        $siteLogoUrl = $this->siteLogoUrl();
        $legacySocialImagePath = $this->model->findSetting('seo_social_image_path');
        if ($legacySocialImagePath !== null) {
            $legacySocialImagePath = trim($legacySocialImagePath);
        }
        $legacyFaviconPath = $this->model->findSetting('site_favicon_path');
        if ($legacyFaviconPath !== null) {
            $legacyFaviconPath = trim($legacyFaviconPath);
        }
        $footerText = $this->model->getSetting('site_footer_text', '');
        $dgName = $this->model->getSetting('platform_dg_name', 'Directeur général');
        $dgSignaturePath = $this->model->getSetting('platform_dg_signature_path', '');
        $dgSignatureUrl = $this->pathToUrl($dgSignaturePath);

        $logoMaxMb = 5;
        $dgSignatureMaxMb = 1;
        $submittedTab = '';

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');
            if ($action === 'save_site_settings') {
                $submittedTab = $this->normalizeSettingsTab((string) ($post['settings_tab'] ?? 'identity'));
                $siteName = trim((string) ($post['site_name'] ?? ''));
                if ($siteName === '') {
                    $errors[] = 'Nom du site obligatoire.';
                } elseif (mb_strlen($siteName) > 120) {
                    $errors[] = 'Nom du site trop long.';
                }

                $siteTagline = trim((string) ($post['site_tagline'] ?? ''));
                if (mb_strlen($siteTagline) > 180) {
                    $errors[] = 'Slogan du site trop long.';
                }

                $siteDescription = trim((string) ($post['site_description'] ?? ''));
                if (mb_strlen($siteDescription) > 600) {
                    $errors[] = 'Description du site trop longue.';
                }

                $supportEmail = trim((string) ($post['support_email'] ?? ''));
                if ($supportEmail !== '' && filter_var($supportEmail, FILTER_VALIDATE_EMAIL) === false) {
                    $errors[] = 'Adresse e-mail de support invalide.';
                }

                $supportPhone = trim((string) ($post['support_phone'] ?? ''));
                if (mb_strlen($supportPhone) > 40) {
                    $errors[] = 'Numéro de support trop long.';
                }

                $supportWhatsapp = trim((string) ($post['support_whatsapp'] ?? ''));
                if (mb_strlen($supportWhatsapp) > 40) {
                    $errors[] = 'Numéro WhatsApp trop long.';
                }

                $siteAddress = trim((string) ($post['site_address'] ?? ''));
                if (mb_strlen($siteAddress) > 240) {
                    $errors[] = 'Adresse du site trop longue.';
                }

                $seoTitle = trim((string) ($post['seo_title'] ?? ''));
                if ($seoTitle === '') {
                    $seoTitle = $siteName;
                } elseif (mb_strlen($seoTitle) > 160) {
                    $errors[] = 'Titre SEO trop long.';
                }

                $seoDescription = trim((string) ($post['seo_description'] ?? ''));
                if (mb_strlen($seoDescription) > 320) {
                    $errors[] = 'Description SEO trop longue.';
                }

                $seoKeywords = trim((string) ($post['seo_keywords'] ?? ''));
                if (mb_strlen($seoKeywords) > 300) {
                    $errors[] = 'Mots-clés SEO trop longs.';
                }

                $maintenanceEnabled = !empty($post['maintenance_enabled']);
                $maintenanceMessage = trim((string) ($post['maintenance_message'] ?? ''));
                if ($maintenanceMessage === '') {
                    $maintenanceMessage = self::DEFAULT_MAINTENANCE_MESSAGE;
                }
                if (mb_strlen($maintenanceMessage) > 500) {
                    $errors[] = 'Message de maintenance trop long.';
                }

                if (array_key_exists('registrations_enabled', $post)) {
                    $registrationsEnabled = !empty($post['registrations_enabled']);
                }
                if (array_key_exists('teacher_applications_enabled', $post)) {
                    $teacherApplicationsEnabled = !empty($post['teacher_applications_enabled']);
                }
                $fedapayEnabled = !empty($post['fedapay_enabled']);
                $storedFedapaySecretKey = $fedapaySecretKey;
                $storedFedapayWebhookSecret = $fedapayWebhookSecret;
                $fedapayPublicKey = trim((string) ($post['fedapay_public_key'] ?? ''));
                $postedFedapaySecretKey = trim((string) ($post['fedapay_secret_key'] ?? ''));
                $fedapayEnvironment = $this->normalizeFedapayEnvironment((string) ($post['fedapay_environment'] ?? 'sandbox'));
                $postedFedapayWebhookSecret = trim((string) ($post['fedapay_webhook_secret'] ?? ''));
                $fedapaySecretKey = !empty($post['fedapay_secret_key_clear'])
                    ? ''
                    : ($postedFedapaySecretKey !== '' ? $postedFedapaySecretKey : $storedFedapaySecretKey);
                $fedapayWebhookSecret = !empty($post['fedapay_webhook_secret_clear'])
                    ? ''
                    : ($postedFedapayWebhookSecret !== '' ? $postedFedapayWebhookSecret : $storedFedapayWebhookSecret);

                if (mb_strlen($fedapayPublicKey) > 255) {
                    $errors[] = 'Clé publique FedaPay trop longue.';
                }
                if (mb_strlen($fedapaySecretKey) > 255) {
                    $errors[] = 'Clé secrète FedaPay trop longue.';
                }
                if (mb_strlen($fedapayWebhookSecret) > 255) {
                    $errors[] = 'Secret webhook FedaPay trop long.';
                }
                if ($fedapayEnabled && ($fedapayPublicKey === '' || $fedapaySecretKey === '')) {
                    $errors[] = 'La clé publique et la clé secrète FedaPay sont obligatoires pour activer le paiement.';
                }

                $footerText = trim((string) ($post['footer_text'] ?? ''));
                if (mb_strlen($footerText) > 2000) {
                    $errors[] = 'Texte du pied de page trop long.';
                }

                $dgName = trim((string) ($post['dg_name'] ?? ''));
                if ($dgName === '') {
                    $errors[] = 'Nom du directeur général obligatoire.';
                } elseif (mb_strlen($dgName) > 120) {
                    $errors[] = 'Nom du directeur général trop long.';
                }

                if (!empty($post['remove_logo']) && $siteLogoPath !== '') {
                    $this->removeManagedLocalFile($siteLogoPath);
                    $siteLogoPath = '';
                    $siteLogoUrl = '';
                    $this->model->setSetting('site_logo_path', '');
                    if ($legacyFaviconPath !== null && $legacyFaviconPath !== '') {
                        $this->removeManagedLocalFile($legacyFaviconPath);
                    }
                    $this->model->deleteSetting('site_favicon_path');
                    $legacyFaviconPath = null;
                }

                if (!empty($post['remove_dg_signature']) && $dgSignaturePath !== '') {
                    $this->removeManagedLocalFile($dgSignaturePath);
                    $dgSignaturePath = '';
                    $dgSignatureUrl = '';
                    $this->model->setSetting('platform_dg_signature_path', '');
                }

                if ($errors === []) {
                    $uploadedLogoPath = $this->handleImageUpload($files['site_logo'] ?? null, 'site_logo', $logoMaxMb, $errors);
                    if ($uploadedLogoPath !== null) {
                        if ($siteLogoPath !== '') {
                            $this->removeManagedLocalFile($siteLogoPath);
                        }
                        $siteLogoPath = $uploadedLogoPath;
                        $siteLogoUrl = $this->siteUrl($uploadedLogoPath);
                        $this->model->setSetting('site_logo_path', $siteLogoPath);
                    }
                }

                if ($errors === []) {
                    $uploadedSignaturePath = $this->handleImageUpload($files['dg_signature'] ?? null, 'dg_signature', $dgSignatureMaxMb, $errors);
                    if ($uploadedSignaturePath !== null) {
                        if ($dgSignaturePath !== '') {
                            $this->removeManagedLocalFile($dgSignaturePath);
                        }
                        $dgSignaturePath = $uploadedSignaturePath;
                        $dgSignatureUrl = $this->siteUrl($dgSignaturePath);
                        $this->model->setSetting('platform_dg_signature_path', $dgSignaturePath);
                    }
                }

                if ($errors === [] && $legacySocialImagePath !== null) {
                    if (trim($legacySocialImagePath) !== '') {
                        $this->removeManagedLocalFile($legacySocialImagePath);
                    }
                    $this->model->deleteSetting('seo_social_image_path');
                    $legacySocialImagePath = null;
                }

                if ($errors === [] && $legacyFaviconPath !== null) {
                    if ($legacyFaviconPath !== '') {
                        $this->removeManagedLocalFile($legacyFaviconPath);
                    }
                    $this->model->deleteSetting('site_favicon_path');
                    $legacyFaviconPath = null;
                }

                if ($errors === []) {
                    $this->model->setSetting('site_name', $siteName);
                    $this->model->setSetting('site_tagline', $siteTagline);
                    $this->model->setSetting('site_description', $siteDescription);
                    $this->model->setSetting('support_email', $supportEmail);
                    $this->model->setSetting('support_phone', $supportPhone);
                    $this->model->setSetting('support_whatsapp', $supportWhatsapp);
                    $this->model->setSetting('site_address', $siteAddress);
                    $this->model->setSetting('seo_title', $seoTitle);
                    $this->model->setSetting('seo_description', $seoDescription);
                    $this->model->setSetting('seo_keywords', $seoKeywords);
                    $this->model->setSetting('maintenance_enabled', $maintenanceEnabled ? '1' : '0');
                    $this->model->setSetting('maintenance_message', $maintenanceMessage);
                    $this->model->setSetting('registrations_enabled', $registrationsEnabled ? '1' : '0');
                    $this->model->setSetting('teacher_applications_enabled', $teacherApplicationsEnabled ? '1' : '0');
                    $this->model->setSetting('payment_fedapay_enabled', $fedapayEnabled ? '1' : '0');
                    $this->model->setSetting('payment_fedapay_public_key', $fedapayPublicKey);
                    $this->model->setSetting('payment_fedapay_secret_key', $fedapaySecretKey);
                    $this->model->setSetting('payment_fedapay_environment', $fedapayEnvironment);
                    $this->model->setSetting('payment_fedapay_webhook_secret', $fedapayWebhookSecret);
                    $this->model->setSetting('site_footer_text', $footerText);
                    $this->model->setSetting('platform_dg_name', $dgName);
                    $success = 'Paramètres du site mis à jour.';
                }
            }
        }

        if ($method === 'POST' && $errors === [] && $success !== '') {
            $this->flashSet('success', $success);
            return [
                'redirectTo' => $this->siteUrl('admin/site_settings?tab=' . ($submittedTab !== '' ? $submittedTab : 'identity')),
                'view' => [],
            ];
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'success' => $success,
                'selectedTab' => $submittedTab !== '' ? $submittedTab : null,
                'siteName' => $siteName,
                'siteTagline' => $siteTagline,
                'siteDescription' => $siteDescription,
                'supportEmail' => $supportEmail,
                'supportPhone' => $supportPhone,
                'supportWhatsapp' => $supportWhatsapp,
                'siteAddress' => $siteAddress,
                'siteLogoPath' => $siteLogoPath,
                'siteLogoUrl' => $siteLogoUrl,
                'seoTitle' => $seoTitle,
                'seoDescription' => $seoDescription,
                'seoKeywords' => $seoKeywords,
                'maintenanceEnabled' => $maintenanceEnabled,
                'maintenanceMessage' => $maintenanceMessage,
                'registrationsEnabled' => $registrationsEnabled,
                'teacherApplicationsEnabled' => $teacherApplicationsEnabled,
                'fedapayEnabled' => $fedapayEnabled,
                'fedapayPublicKey' => $fedapayPublicKey,
                'fedapaySecretKey' => $fedapaySecretKey,
                'fedapayEnvironment' => $fedapayEnvironment,
                'fedapayWebhookSecret' => $fedapayWebhookSecret,
                'footerText' => $footerText,
                'dgName' => $dgName,
                'dgSignaturePath' => $dgSignaturePath,
                'dgSignatureUrl' => $dgSignatureUrl,
                'logoMaxMb' => $logoMaxMb,
                'dgSignatureMaxMb' => $dgSignatureMaxMb,
            ],
        ];
    }

    private function normalizeFedapayEnvironment(string $environment): string
    {
        $environment = strtolower(trim($environment));
        if ($environment === 'production') {
            return 'live';
        }
        if ($environment === 'development') {
            return 'dev';
        }
        if (!in_array($environment, ['sandbox', 'live', 'dev'], true)) {
            return 'sandbox';
        }

        return $environment;
    }

    private function settingWithFallback(string $key, string $default = ''): string
    {
        $value = trim($this->model->getSetting($key, $default));
        if ($value === '' && $default !== '') {
            return trim($default);
        }

        return $value;
    }

    private function normalizeSettingsTab(string $tab): string
    {
        $tab = strtolower(trim($tab));
        if (!in_array($tab, ['identity', 'brand', 'seo', 'access', 'payments', 'certificate'], true)) {
            return 'identity';
        }

        return $tab;
    }

    private function pathToUrl(string $path): string
    {
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        $localPath = __DIR__ . '/../../../' . ltrim($path, '/');
        if (!is_file($localPath)) {
            return '';
        }

        return $this->siteUrl(ltrim($path, '/'));
    }

    private function removeManagedLocalFile(string $path): void
    {
        $localPath = __DIR__ . '/../../../' . ltrim($path, '/');
        if (str_starts_with($path, 'storage/uploads/site/') && is_file($localPath)) {
            @unlink($localPath);
        }
    }

    /**
     * @param mixed $file
     * @param array<int,string> $errors
     */
    private function handleImageUpload($file, string $prefix, int $maxMb, array &$errors): ?string
    {
        if (!is_array($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errors[] = "Erreur d'upload " . $this->uploadLabel($prefix) . ' (code ' . (string) $uploadError . ').';
            return null;
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $sizeBytes = (int) ($file['size'] ?? 0);
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            $errors[] = 'Fichier ' . $this->uploadLabel($prefix) . ' invalide.';
            return null;
        }

        $maxBytes = $maxMb * 1024 * 1024;
        if ($maxBytes > 0 && $sizeBytes > $maxBytes) {
            $errors[] = ucfirst($this->uploadLabel($prefix)) . ' trop volumineux (max ' . (string) $maxMb . ' Mo).';
            return null;
        }

        $mimeType = $this->detectMimeType($tmpPath, (string) ($file['type'] ?? ''));
        $allowed = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
        ];
        if (empty($allowed[$mimeType])) {
            $errors[] = 'Format ' . $this->uploadLabel($prefix) . ' invalide (PNG, JPG, WebP).';
            return null;
        }

        if ($prefix === 'dg_signature') {
            $dimensions = @getimagesize($tmpPath);
            $width = is_array($dimensions) ? (int) ($dimensions[0] ?? 0) : 0;
            $height = is_array($dimensions) ? (int) ($dimensions[1] ?? 0) : 0;
            if ($mimeType !== 'image/png' || $width !== 800 || $height !== 240) {
                $errors[] = 'La signature DG doit être un PNG transparent de 800 × 240 px.';
                return null;
            }
        }

        $uploadDir = __DIR__ . '/../../../storage/uploads/site';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = $allowed[$mimeType];
        try {
            $randomHex = bin2hex(random_bytes(4));
        } catch (\Throwable $e) {
            $randomHex = substr(sha1((string) microtime(true)), 0, 8);
        }

        $filename = $prefix . '_' . date('Ymd_His') . '_' . $randomHex . '.' . $ext;
        $dest = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $dest)) {
            $errors[] = "Échec de l'upload " . $this->uploadLabel($prefix) . '.';
            return null;
        }

        return 'storage/uploads/site/' . $filename;
    }

    private function detectMimeType(string $tmpPath, string $fallback): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    return $detected;
                }
            }
        }

        return $fallback !== '' ? $fallback : 'application/octet-stream';
    }

    private function uploadLabel(string $prefix): string
    {
        if ($prefix === 'site_logo') {
            return 'logo';
        }
        if ($prefix === 'dg_signature') {
            return 'signature DG';
        }
        return 'fichier';
    }
}
