<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;
use App\Models\WebProfileModel;
use App\Services\Shared\CertificateOpsService;
use DateTime;
use Throwable;

final class ProfileService extends Service
{
    private WebProfileModel $profiles;
    private CertificateOpsService $certificates;

    public function __construct(WebProfileModel $profiles, CertificateOpsService $certificates)
    {
        $this->profiles = $profiles;
        $this->certificates = $certificates;
    }

    public function handle(array $user, string $requestMethod, array $post, array $files): array
    {
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true);
        $roles = is_array($roles) ? $roles : [];
        $isTeacher = in_array('teacher', $roles, true);
        $isStudent = in_array('student', $roles, true) || $isTeacher;

        $this->profiles->ensureExtendedProfileSchema();
        $freshUser = $this->profiles->findUserById((int) ($user['id'] ?? 0));
        if (is_array($freshUser)) {
            $user = array_merge($user, $freshUser);
        }

        $hasSignatureColumn = $this->certificates->ensureUsersSignatureColumn();

        $profileMessage = (string) $this->flashGet('profile_message', '');
        $profileErrors = [];
        $passwordMessage = (string) $this->flashGet('password_message', '');
        $passwordErrors = [];

        $displayUser = $user;
        $studentProfile = $isStudent ? $this->profiles->findStudentProfileByUserId((int) ($user['id'] ?? 0)) : ['certificate_display_name' => ''];
        $teacherProfile = $isTeacher ? $this->profiles->findTeacherProfileByUserId((int) ($user['id'] ?? 0)) : $this->defaultTeacherProfile();
        $notificationPreferences = $this->normalizeNotificationPreferences($displayUser['notification_preferences_json'] ?? null);

        $avatarMaxMb = 2;
        $signatureMaxMb = 1;
        $authProvider = strtolower(trim((string) ($displayUser['auth_provider'] ?? 'local')));
        if ($authProvider === '') {
            $authProvider = 'local';
        }
        $isGoogleAccount = $authProvider === 'google';

        if (strtoupper($requestMethod) === 'POST') {
            $action = (string) ($post['action'] ?? '');
            if ($action === 'profile') {
                $result = $this->handleProfileUpdate(
                    $displayUser,
                    $studentProfile,
                    $teacherProfile,
                    $notificationPreferences,
                    $isStudent,
                    $isTeacher,
                    $hasSignatureColumn,
                    $post,
                    $files,
                    $avatarMaxMb,
                    $signatureMaxMb
                );
                $displayUser = $result['user'];
                $studentProfile = $result['studentProfile'];
                $teacherProfile = $result['teacherProfile'];
                $notificationPreferences = $result['notificationPreferences'];
                $profileErrors = $result['errors'];
                $profileMessage = $result['message'];
                if (!empty($result['redirect'])) {
                    return ['redirect' => $this->siteUrl('profile')];
                }
            }

            if ($action === 'password') {
                if ($isGoogleAccount) {
                    $profileErrors[] = 'Compte Google : le mot de passe se gère directement sur Google.';
                } else {
                    $result = $this->handlePasswordUpdate($displayUser, $post);
                    $passwordErrors = $result['errors'];
                    $passwordMessage = $result['message'];
                    if (!empty($result['redirect'])) {
                        return ['redirect' => $this->siteUrl('profile')];
                    }
                }
            }
        }

        if (strtoupper($requestMethod) === 'POST' && (string) ($post['action'] ?? '') === 'delete_account') {
            $result = $this->handleAccountDeletion($displayUser, $post);
            $profileErrors = $result['errors'];
            $profileMessage = $result['message'];
            if (!empty($result['redirect'])) {
                return ['redirect' => (string) $result['redirect']];
            }
        }

        $avatarUrl = trim((string) ($displayUser['avatar_url'] ?? ''));
        $needsAvatar = $avatarUrl === '';
        $avatarSrc = $this->toAssetUrl($avatarUrl);

        $signaturePath = $hasSignatureColumn ? trim((string) ($displayUser['signature_path'] ?? '')) : '';
        $signatureUrl = $this->toAssetUrl($signaturePath);
        $teacherCvUrl = $this->toAssetUrl(trim((string) ($teacherProfile['cv_path'] ?? '')));

        return [
            'user' => $displayUser,
            'isTeacher' => $isTeacher,
            'isStudent' => $isStudent,
            'studentProfile' => $studentProfile,
            'teacherProfile' => $teacherProfile,
            'notificationPreferences' => $notificationPreferences,
            'teacherCvUrl' => $teacherCvUrl,
            'hasSignatureColumn' => $hasSignatureColumn,
            'profileMessage' => $profileMessage,
            'profileErrors' => $profileErrors,
            'passwordMessage' => $passwordMessage,
            'passwordErrors' => $passwordErrors,
            'avatarMaxMb' => $avatarMaxMb,
            'signatureMaxMb' => $signatureMaxMb,
            'needsAvatar' => $needsAvatar,
            'avatarSrc' => $avatarSrc,
            'signatureUrl' => $signatureUrl,
            'authProvider' => $authProvider,
            'isGoogleAccount' => $isGoogleAccount,
            'siteLogoUrl' => $this->siteLogoUrl(),
            'redirect' => null,
        ];
    }

    private function handleProfileUpdate(
        array $user,
        array $studentProfile,
        array $teacherProfile,
        array $notificationPreferences,
        bool $isStudent,
        bool $isTeacher,
        bool $hasSignatureColumn,
        array $post,
        array $files,
        int $avatarMaxMb,
        int $signatureMaxMb
    ): array {
        $errors = [];
        $message = '';
        $originalEmail = (string) ($user['email'] ?? '');

        $firstName = trim((string) ($post['first_name'] ?? ''));
        $lastName = trim((string) ($post['last_name'] ?? ''));
        $email = trim((string) ($post['email'] ?? ($user['email'] ?? '')));
        $phone = trim((string) ($post['phone'] ?? ''));
        $phone = $this->normalizePhoneInput($phone);
        $country = trim((string) ($post['country'] ?? ''));

        $studentProfile['certificate_display_name'] = trim((string) ($post['certificate_display_name'] ?? ($studentProfile['certificate_display_name'] ?? '')));
        $teacherProfile = $this->normalizeTeacherProfileInput($teacherProfile, $post);
        $notificationRaw = array_key_exists('notification_preferences_present', $post)
            ? ($post['notification_preferences'] ?? [])
            : ($post['notification_preferences'] ?? $notificationPreferences);
        $notificationPreferences = $this->normalizeNotificationPreferences($notificationRaw);

        $user['first_name'] = $firstName;
        $user['last_name'] = $lastName;
        $user['email'] = $email;
        $user['phone'] = $phone;
        $user['country'] = $country;
        $user['notification_preferences_json'] = json_encode($notificationPreferences);

        if ($firstName === '' || $lastName === '') {
            $errors[] = 'Prénom et nom sont obligatoires.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        } elseif ($this->profiles->emailExistsForOtherUser($email, (int) $user['id'])) {
            $errors[] = 'Cet email est déjà utilisé.';
        }

        if ($isStudent) {
            $certificateDisplayName = trim((string) ($studentProfile['certificate_display_name'] ?? ''));
            if ($certificateDisplayName !== '' && strlen($certificateDisplayName) > 180) {
                $errors[] = 'Le nom du certificat ne doit pas dépasser 180 caractères.';
            }
        }

        if ($isTeacher) {
            $professionalTitle = trim((string) ($teacherProfile['professional_title'] ?? ''));
            $shortBio = trim((string) ($teacherProfile['short_bio'] ?? ''));

            if ($professionalTitle === '') {
                $errors[] = 'Le titre professionnel enseignant est obligatoire.';
            } elseif (strlen($professionalTitle) > 150) {
                $errors[] = 'Le titre professionnel ne doit pas dépasser 150 caractères.';
            }

            if ($shortBio === '') {
                $errors[] = 'La courte biographie enseignant est obligatoire.';
            } elseif (strlen($shortBio) > 1200) {
                $errors[] = 'La courte biographie ne doit pas dépasser 1200 caractères.';
            }

            foreach (['portfolio_url' => 'portfolio', 'linkedin_url' => 'LinkedIn'] as $field => $label) {
                $value = trim((string) ($teacherProfile[$field] ?? ''));
                if ($value !== '' && filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $errors[] = 'Le lien ' . $label . ' est invalide.';
                }
            }

            if ($phone === '') {
                $errors[] = 'Numéro de retrait obligatoire.';
            }
        }

        [$phoneNormalized, $phoneErrors] = $this->validatePhone($phone);
        $errors = array_merge($errors, $phoneErrors);

        $avatarPath = trim((string) ($user['avatar_url'] ?? ''));
        [$avatarUploadPath, $avatarErrors] = $this->handleImageUpload(
            $files['avatar_file'] ?? null,
            $avatarMaxMb,
            'avatars',
            'avatar_',
            $avatarPath
        );
        $errors = array_merge($errors, $avatarErrors);
        if ($avatarUploadPath !== null) {
            $avatarPath = $avatarUploadPath;
        }

        $signaturePath = $hasSignatureColumn ? trim((string) ($user['signature_path'] ?? '')) : '';
        if ($isTeacher && $hasSignatureColumn && !empty($post['remove_signature'])) {
            $this->removeLocalFileIfOwned($signaturePath, 'storage/uploads/signatures/');
            $signaturePath = '';
        }
        if ($isTeacher && $hasSignatureColumn) {
            [$signatureUploadPath, $signatureErrors] = $this->handleImageUpload(
                $files['signature_file'] ?? null,
                $signatureMaxMb,
                'signatures',
                'signature_',
                $signaturePath,
                true
            );
            $errors = array_merge($errors, $signatureErrors);
            if ($signatureUploadPath !== null) {
                if ($signaturePath !== '') {
                    $this->removeLocalFileIfOwned($signaturePath, 'storage/uploads/signatures/');
                }
                $signaturePath = $signatureUploadPath;
            }
        }

        $user['avatar_url'] = $avatarPath !== '' ? $avatarPath : null;
        if ($hasSignatureColumn) {
            $user['signature_path'] = $signaturePath !== '' ? $signaturePath : null;
        }

        if ($errors !== []) {
            return [
                'user' => $user,
                'studentProfile' => $studentProfile,
                'teacherProfile' => $teacherProfile,
                'notificationPreferences' => $notificationPreferences,
                'errors' => $errors,
                'message' => $message,
                'redirect' => null,
            ];
        }

        try {
            $this->profiles->beginTransaction();

            $emailChanged = strcasecmp($email, $originalEmail) !== 0;
            $status = $emailChanged ? 'pending_email' : (string) ($user['status'] ?? 'active');
            $verifiedAt = $emailChanged ? null : ($user['email_verified_at'] ?? null);

            $this->profiles->updateProfile((int) $user['id'], [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phoneNormalized !== '' ? $phoneNormalized : null,
                'country' => $country !== '' ? $country : null,
                'avatar_url' => $avatarPath !== '' ? $avatarPath : null,
                'signature_path' => $signaturePath !== '' ? $signaturePath : null,
                'notification_preferences_json' => $notificationPreferences,
                'status' => $status,
                'email_verified_at' => $verifiedAt,
            ], $hasSignatureColumn);

            if ($isStudent) {
                $certificateDisplayName = trim((string) ($studentProfile['certificate_display_name'] ?? ''));
                $this->profiles->upsertStudentProfile(
                    (int) $user['id'],
                    $certificateDisplayName !== '' ? $certificateDisplayName : null
                );
            }

            if ($isTeacher) {
                $this->profiles->upsertTeacherProfile((int) $user['id'], [
                    'professional_title' => $teacherProfile['professional_title'] ?? null,
                    'short_bio' => $teacherProfile['short_bio'] ?? null,
                    'portfolio_url' => $teacherProfile['portfolio_url'] ?? null,
                    'linkedin_url' => $teacherProfile['linkedin_url'] ?? null,
                    'cv_path' => $teacherProfile['cv_path'] ?? null,
                ]);
            }

            if ($emailChanged) {
                $this->profiles->invalidateEmailVerifications((int) $user['id']);
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = (new DateTime('+1 day'))->format('Y-m-d H:i:s');
                $this->profiles->insertEmailVerification((int) $user['id'], $tokenHash, $expiresAt);

                $verificationLink = $this->absUrl('verify?token=' . urlencode($token));
                $mailBody = "Bonjour {$firstName},\n\nMerci de confirmer votre nouvel email : {$verificationLink}\n\nSi vous n'êtes pas à l'origine de cette modification, ignorez cet email.";
                $this->sendEmail($email, 'Vérification de votre email', $mailBody);

                $message = 'Profil mis à jour. Vérifiez le nouvel email pour réactiver votre compte.';
                $mailError = function_exists('last_mail_error') ? \last_mail_error() : [];
                if ($mailError !== []) {
                    if (function_exists('app_log_error')) {
                        \app_log_error('web.profile', 'Email de verification non envoye lors du changement d email.', [
                            'user_id' => (int) ($user['id'] ?? 0),
                            'email' => $email,
                            'mail_error' => $mailError,
                        ]);
                    }
                    throw new \RuntimeException('profile_email_delivery_failed');
                }
            } else {
                $message = 'Profil mis à jour.';
            }

            $this->profiles->commit();

            $this->flashSet('profile_message', $message);
            return [
                'user' => $user,
                'studentProfile' => $studentProfile,
                'teacherProfile' => $teacherProfile,
                'notificationPreferences' => $notificationPreferences,
                'errors' => [],
                'message' => $message,
                'redirect' => true,
            ];
        } catch (Throwable $e) {
            if (function_exists('app_log_error')) {
                \app_log_error('web.profile', 'Mise a jour du profil en erreur.', [
                    'user_id' => (int) ($user['id'] ?? 0),
                    'email' => $email ?? '',
                    'exception' => $e,
                ]);
            }
            if ($this->profiles->inTransaction()) {
                $this->profiles->rollBack();
            }
            $errors[] = 'Erreur lors de la mise à jour du profil.';
            return [
                'user' => $user,
                'studentProfile' => $studentProfile,
                'teacherProfile' => $teacherProfile,
                'notificationPreferences' => $notificationPreferences,
                'errors' => $errors,
                'message' => '',
                'redirect' => null,
            ];
        }
    }

    private function handlePasswordUpdate(array $user, array $post): array
    {
        $errors = [];
        $message = '';

        $currentPassword = (string) ($post['current_password'] ?? '');
        $newPassword = (string) ($post['new_password'] ?? '');
        $confirmPassword = (string) ($post['confirm_password'] ?? '');

        if (!password_verify($currentPassword, (string) ($user['password_hash'] ?? ''))) {
            $errors[] = 'Mot de passe actuel incorrect.';
        }
        if (strlen($newPassword) < 8) {
            $errors[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }

        if ($errors === []) {
            $this->profiles->updatePassword((int) $user['id'], password_hash($newPassword, PASSWORD_DEFAULT));
            $this->clearRememberCookies();
            $message = 'Mot de passe mis à jour.';
            $this->flashSet('password_message', $message);
            return ['errors' => [], 'message' => $message, 'redirect' => true];
        }

        return ['errors' => $errors, 'message' => $message, 'redirect' => null];
    }

    private function handleAccountDeletion(array $user, array $post): array
    {
        $errors = [];
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) {
            return ['errors' => ['Compte introuvable.'], 'message' => '', 'redirect' => null];
        }

        $roles = $this->decodeRoles($user['roles_json'] ?? null);
        foreach (['admin', 'admin_secondary', 'support'] as $protectedRole) {
            if (in_array($protectedRole, $roles, true)) {
                $errors[] = 'Les comptes avec des droits d administration ou de support ne peuvent pas etre supprimes depuis cette page.';
                break;
            }
        }

        if ($this->profiles->countOwnedCompanies($userId) > 0 || $this->profiles->countCompanyAdminMemberships($userId) > 0) {
            $errors[] = 'Ce compte gere encore une organisation entreprise. Transferez d abord l administration avant de supprimer le compte.';
        }

        if ((string) ($post['delete_confirmation'] ?? '') !== 'SUPPRIMER') {
            $errors[] = 'Tapez exactement SUPPRIMER pour confirmer la suppression definitive.';
        }

        if (empty($post['delete_acknowledge'])) {
            $errors[] = 'Confirmez avoir compris que la suppression est irreversible.';
        }

        $authProvider = strtolower(trim((string) ($user['auth_provider'] ?? 'local')));
        if ($authProvider !== 'google') {
            $deletePassword = (string) ($post['delete_account_password'] ?? '');
            if ($deletePassword === '' || !password_verify($deletePassword, (string) ($user['password_hash'] ?? ''))) {
                $errors[] = 'Mot de passe de confirmation incorrect.';
            }
        }

        if ($errors !== []) {
            return ['errors' => $errors, 'message' => '', 'redirect' => null];
        }

        $filePaths = $this->profiles->collectAccountFilePaths($userId);
        $replacementEmail = $this->deletedEmailAlias($userId);

        try {
            $this->profiles->beginTransaction();
            $this->profiles->purgeAccountAncillaryData($userId);
            $this->profiles->anonymizeAccount($userId, $replacementEmail);
            $this->profiles->commit();
        } catch (Throwable $e) {
            if ($this->profiles->inTransaction()) {
                $this->profiles->rollBack();
            }

            return [
                'errors' => ['Impossible de supprimer le compte pour le moment.'],
                'message' => '',
                'redirect' => null,
            ];
        }

        foreach ($filePaths as $path) {
            $this->removeLocalFileIfOwned($path, 'storage/uploads/');
        }

        $this->logoutUser();

        return [
            'errors' => [],
            'message' => '',
            'redirect' => $this->siteUrl('login?account_deleted=1'),
        ];
    }

    private function validatePhone(string $phone): array
    {
        $errors = [];
        $normalizedPhone = $this->normalizePhoneInput($phone);
        $phoneDigits = preg_replace('/\D+/', '', $normalizedPhone);

        if ($normalizedPhone !== '' && ($phoneDigits === '' || strlen($phoneDigits) < 6 || strlen($phoneDigits) > 15)) {
            $errors[] = 'Numéro de téléphone invalide.';
        }

        return [$normalizedPhone, $errors];
    }

    private function normalizePhoneInput(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        $phone = preg_replace('/[^\d+]+/', '', $phone);
        if (!is_string($phone)) {
            return '';
        }

        $phone = preg_replace('/(?!^)\+/', '', $phone);
        if (!is_string($phone)) {
            return '';
        }

        return $phone;
    }

    private function handleImageUpload(
        ?array $file,
        int $maxMb,
        string $folder,
        string $prefix,
        string $currentPath,
        bool $requireSignatureCanvas = false
    ): array
    {
        if (!$file || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, []];
        }

        $errors = [];
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            return [null, ['Erreur upload fichier (code ' . $errorCode . ').']];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            $errors[] = 'Fichier invalide.';
        }

        $maxBytes = $maxMb * 1024 * 1024;
        if ($size > $maxBytes) {
            $errors[] = 'Le fichier depasse ' . $maxMb . ' Mo.';
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMime = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp'];
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, $allowedExt, true)) {
            $errors[] = 'Format invalide (jpg, png, webp).';
        }

        $mime = '';
        if ($errors === [] && class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = $finfo->file($tmpName);
                if (is_string($detected) && $detected !== '') {
                    $mime = $detected;
                }
            }
        }
        if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
            $errors[] = 'Format invalide (jpg, png, webp).';
        }

        if ($requireSignatureCanvas) {
            if ($mime === '') {
                $mime = strtolower(trim((string) ($file['type'] ?? '')));
            }
            if ($mime !== 'image/png') {
                $errors[] = 'La signature doit être convertie en PNG transparent.';
            }
            $dimensions = @getimagesize($tmpName);
            $width = is_array($dimensions) ? (int) ($dimensions[0] ?? 0) : 0;
            $height = is_array($dimensions) ? (int) ($dimensions[1] ?? 0) : 0;
            if ($width !== 800 || $height !== 240) {
                $errors[] = 'Dimensions de signature invalides : 800 × 240 px attendus.';
            }
        }

        if ($errors !== []) {
            return [null, $errors];
        }

        $root = dirname(__DIR__, 3);
        $uploadDir = $root . '/storage/uploads/' . $folder;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = $prefix . bin2hex(random_bytes(10)) . '.' . $ext;
        $destination = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($tmpName, $destination)) {
            return [null, ['Erreur lors de l upload du fichier.']];
        }

        if ($currentPath !== '') {
            $ownedPrefix = 'storage/uploads/' . $folder . '/';
            $this->removeLocalFileIfOwned($currentPath, $ownedPrefix);
        }

        return ['storage/uploads/' . $folder . '/' . $filename, []];
    }

    private function removeLocalFileIfOwned(string $path, string $ownedPrefix): void
    {
        if (!str_starts_with($path, $ownedPrefix)) {
            return;
        }
        $localPath = dirname(__DIR__, 3) . '/' . ltrim($path, '/');
        if (is_file($localPath)) {
            @unlink($localPath);
        }
    }

    private function defaultNotificationPreferences(): array
    {
        return [
            'email_announcements' => true,
            'email_support' => true,
            'email_meetings' => true,
            'popup_announcements' => true,
            'popup_support' => true,
        ];
    }

    private function normalizeNotificationPreferences(mixed $raw): array
    {
        $defaults = $this->defaultNotificationPreferences();
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            return $defaults;
        }

        $normalized = $defaults;
        foreach (array_keys($defaults) as $key) {
            $normalized[$key] = !empty($raw[$key]);
        }
        return $normalized;
    }

    private function defaultTeacherProfile(): array
    {
        return [
            'professional_title' => '',
            'short_bio' => '',
            'portfolio_url' => '',
            'linkedin_url' => '',
            'cv_path' => '',
        ];
    }

    private function normalizeTeacherProfileInput(array $current, array $post): array
    {
        $profile = array_merge($this->defaultTeacherProfile(), $current);
        $profile['professional_title'] = trim((string) ($post['professional_title'] ?? ($profile['professional_title'] ?? '')));
        $profile['short_bio'] = trim((string) ($post['short_bio'] ?? ($profile['short_bio'] ?? '')));
        $profile['portfolio_url'] = trim((string) ($post['portfolio_url'] ?? ($profile['portfolio_url'] ?? '')));
        $profile['linkedin_url'] = trim((string) ($post['linkedin_url'] ?? ($profile['linkedin_url'] ?? '')));
        $profile['cv_path'] = trim((string) ($profile['cv_path'] ?? ''));
        return $profile;
    }

    /**
     * @return array<int,string>
     */
    private function decodeRoles(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $raw = json_decode($raw, true);
        }
        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $role): string => trim((string) $role), $raw),
            static fn (string $role): bool => $role !== ''
        ));
    }

    private function deletedEmailAlias(int $userId): string
    {
        return sprintf('deleted+%d+%s@invalid.local', $userId, date('YmdHis'));
    }

    private function toAssetUrl(string $path): string
    {
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\\/\\//i', $path) === 1) {
            return $path;
        }
        return $this->siteUrl(ltrim($path, '/'));
    }
}
