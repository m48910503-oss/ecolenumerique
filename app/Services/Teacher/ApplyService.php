<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;
use App\Models\NotificationModel;
use App\Models\TeacherApplicationModel;
use App\Models\UserModel;
use App\Models\WebProfileModel;
use App\Services\Drive\DriveGateway;

final class ApplyService extends Service
{
    private const COMPANY_TEACHER_BLOCK_MESSAGE = 'Les comptes entreprise ne peuvent pas devenir enseignants. Utilisez un compte personnel distinct pour déposer une candidature.';
    private const ALLOWED_CV_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_CV_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/jpg',
        'image/pjpeg',
        'image/png',
        'image/webp',
    ];
    private const CV_FORMAT_ERROR = 'Le CV doit être un fichier PDF, Word ou une image (JPG, PNG ou WebP).';
    private const TEACHING_EXPERIENCE_LABELS = [
        'regular' => 'Oui, régulièrement',
        'occasional' => 'Oui, occasionnellement',
        'beginner' => 'Non, mais je souhaite commencer',
    ];

    private \PDO $pdo;
    private TeacherApplicationModel $applications;
    private UserModel $users;
    private NotificationModel $notifications;
    private WebProfileModel $profiles;

    public function __construct(
        \PDO $pdo,
        TeacherApplicationModel $applications,
        UserModel $users,
        NotificationModel $notifications
    ) {
        $this->pdo = $pdo;
        $this->applications = $applications;
        $this->users = $users;
        $this->notifications = $notifications;
        $this->profiles = new WebProfileModel($pdo);
    }

    public function buildInitialState(array $user, array $roles, array $post = []): array
    {
        $drive = new DriveGateway();
        $alreadyTeacher = in_array('teacher', $roles, true);
        $isCompanyAccount = in_array('company_admin', $roles, true) || in_array('company_member', $roles, true);
        $latestApplication = $this->applications->findLatestByUserId((int) ($user['id'] ?? 0));
        $pendingApplication = is_array($latestApplication) && (string) ($latestApplication['status'] ?? '') === 'pending';
        $this->profiles->ensureExtendedProfileSchema();
        $teacherProfile = $this->profiles->findTeacherProfileByUserId((int) ($user['id'] ?? 0));
        $profileMissingFields = $this->profileMissingFields($user);

        $state = [
            'errors' => [],
            'cvErrors' => [],
            'successMessage' => '',
            'alreadyTeacher' => $alreadyTeacher,
            'pendingApplication' => $pendingApplication,
            'isCompanyAccount' => $isCompanyAccount,
            'formLocked' => $alreadyTeacher || $pendingApplication || $isCompanyAccount,
            'profileIncomplete' => $profileMissingFields !== [],
            'profileMissingFields' => $profileMissingFields,
            'driveReady' => $drive->isConfigured(),
            'driveNeedsConnect' => $drive->oauthConfigured() && !$drive->oauthTokenReady(),
            'driveMaxMb' => APP_DRIVE_MAX_UPLOAD_MB > 0 ? APP_DRIVE_MAX_UPLOAD_MB : 25,
            'title' => (string) ($post['title'] ?? ($teacherProfile['professional_title'] ?? '')),
            'bio' => (string) ($post['bio'] ?? ($teacherProfile['short_bio'] ?? '')),
            'teaching_experience' => (string) ($post['teaching_experience'] ?? ''),
            'portfolio_url' => (string) ($post['portfolio_url'] ?? ($teacherProfile['portfolio_url'] ?? '')),
            'linkedin_url' => (string) ($post['linkedin_url'] ?? ($teacherProfile['linkedin_url'] ?? '')),
            'cvPath' => (string) ($teacherProfile['cv_path'] ?? ''),
        ];

        if ($isCompanyAccount) {
            $state['errors'][] = self::COMPANY_TEACHER_BLOCK_MESSAGE;
            return $state;
        }

        return $state;
    }

    public function submit(array $state, array $user, array $files): array
    {
        $drive = new DriveGateway();
        if (!is_array($state['errors'] ?? null)) {
            $state['errors'] = [];
        }
        if (!empty($state['isCompanyAccount'])) {
            if (!in_array(self::COMPANY_TEACHER_BLOCK_MESSAGE, $state['errors'], true)) {
                $state['errors'][] = self::COMPANY_TEACHER_BLOCK_MESSAGE;
            }
            $state['formLocked'] = true;
            return $state;
        }
        if (!empty($state['alreadyTeacher'])) {
            $state['errors'][] = "Vous \u{00ea}tes d\u{00e9}j\u{00e0} approuv\u{00e9} en tant qu'enseignant.";
            return $state;
        }
        if (!empty($state['pendingApplication'])) {
            $state['errors'][] = "Votre demande est d\u{00e9}j\u{00e0} en cours d’examen.";
            return $state;
        }

        $profileMissingFields = $this->profileMissingFields($user);
        $state['profileMissingFields'] = $profileMissingFields;
        $state['profileIncomplete'] = $profileMissingFields !== [];
        if ($profileMissingFields !== []) {
            $state['errors'][] = 'Complétez votre profil avant l’envoi : ' . implode(', ', $profileMissingFields) . '.';
        }

        $state['title'] = trim((string) $state['title']);
        $state['bio'] = trim((string) $state['bio']);
        $state['teaching_experience'] = trim((string) ($state['teaching_experience'] ?? ''));
        $state['portfolio_url'] = trim((string) $state['portfolio_url']);
        $state['linkedin_url'] = trim((string) $state['linkedin_url']);

        if ($state['title'] === '') {
            $state['errors'][] = 'La compétence principale à enseigner est obligatoire.';
        }
        if ($state['bio'] === '') {
            $state['errors'][] = 'La présentation est obligatoire.';
        }
        if ($state['teaching_experience'] === '') {
            $state['errors'][] = "Indiquez si vous avez déjà enseigné ou accompagné des apprenants.";
        } elseif (!array_key_exists((string) $state['teaching_experience'], self::TEACHING_EXPERIENCE_LABELS)) {
            $state['errors'][] = "L'expérience pédagogique sélectionnée est invalide.";
        }
        foreach (['portfolio_url' => 'portfolio', 'linkedin_url' => 'LinkedIn'] as $field => $label) {
            $value = trim((string) ($state[$field] ?? ''));
            if ($value !== '' && filter_var($value, FILTER_VALIDATE_URL) === false) {
                $state['errors'][] = 'Le lien ' . $label . ' est invalide.';
            }
        }

        if (!$state['driveReady']) {
            $state['errors'][] = !empty($state['driveNeedsConnect'])
                ? "Drive OAuth non connect\u{00e9}. Utilisez le lien de connexion."
                : "D\u{00e9}p\u{00f4}t CV indisponible (configuration Drive manquante).";
        }

        $cvFile = $files['cv_file'] ?? null;
        if (!$cvFile || !isset($cvFile['error'])) {
            $state['errors'][] = 'CV ou justificatif requis.';
            $state['cvErrors'][] = 'Ajoutez votre CV avant d’envoyer votre candidature.';
            return $state;
        }

        $uploadError = (int) ($cvFile['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            $state['errors'][] = 'CV ou justificatif requis.';
            $state['cvErrors'][] = 'Ajoutez votre CV avant d’envoyer votre candidature.';
        } elseif ($uploadError !== UPLOAD_ERR_OK) {
            $state['errors'][] = "Erreur d'upload (code " . (string) $uploadError . ').';
            $state['cvErrors'][] = 'Le fichier de candidature n’a pas pu être envoyé correctement.';
        }

        $tmpPath = '';
        $sizeBytes = 0;
        if (empty($state['errors'])) {
            $tmpPath = (string) ($cvFile['tmp_name'] ?? '');
            $sizeBytes = (int) ($cvFile['size'] ?? 0);
            if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                $state['errors'][] = 'Fichier CV invalide.';
                $state['cvErrors'][] = self::CV_FORMAT_ERROR;
            }
            $maxBytes = (int) $state['driveMaxMb'] * 1024 * 1024;
            if ($maxBytes > 0 && $sizeBytes > $maxBytes) {
                $state['errors'][] = 'Fichier trop volumineux (max ' . (string) $state['driveMaxMb'] . ' Mo).';
                $state['cvErrors'][] = 'Le fichier dépasse ' . (string) $state['driveMaxMb'] . ' Mo.';
            }
        }

        $mimeType = 'application/octet-stream';
        if (empty($state['errors'])) {
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detected = finfo_file($finfo, $tmpPath);
                    if (is_string($detected) && $detected !== '') {
                        $mimeType = $detected;
                    }
                    finfo_close($finfo);
                }
            } elseif (!empty($cvFile['type'])) {
                $mimeType = (string) $cvFile['type'];
            }
            $extension = strtolower(pathinfo((string) ($cvFile['name'] ?? ''), PATHINFO_EXTENSION));
            if (!in_array($extension, self::ALLOWED_CV_EXTENSIONS, true)) {
                $state['errors'][] = self::CV_FORMAT_ERROR;
                $state['cvErrors'][] = self::CV_FORMAT_ERROR;
            } elseif (!in_array($mimeType, self::ALLOWED_CV_MIME_TYPES, true)) {
                $state['errors'][] = self::CV_FORMAT_ERROR;
                $state['cvErrors'][] = self::CV_FORMAT_ERROR;
            }
        }

        if (!empty($state['errors'])) {
            return $state;
        }

        try {
            $this->pdo->beginTransaction();

            $existing = $this->applications->findLatestByUserId((int) $user['id']);
            if ($existing && ($existing['status'] ?? '') === 'pending') {
                $state['errors'][] = "Une demande est d\u{00e9}j\u{00e0} en attente.";
            } elseif ($existing && ($existing['status'] ?? '') === 'approved') {
                $state['errors'][] = "Vous \u{00ea}tes d\u{00e9}j\u{00e0} approuv\u{00e9} en tant qu'enseignant.";
                $state['formLocked'] = true;
            } else {
                $safeOriginal = $drive->safeFilename((string) ($cvFile['name'] ?? 'cv.pdf'));
                $prefix = 'cv_enseignant_' . (int) $user['id'] . '_' . date('Ymd_His');
                $driveName = $prefix . '_' . $safeOriginal;
                if (strlen($driveName) > 220) {
                    $driveName = substr($driveName, 0, 220);
                }

                $parentId = APP_DRIVE_ROOT_FOLDER_ID !== '' ? APP_DRIVE_ROOT_FOLDER_ID : null;
                $driveResult = $drive->uploadFile($tmpPath, $driveName, $mimeType, $parentId, APP_DRIVE_PUBLIC_LINK);

                if (empty($driveResult['ok'])) {
                    $driveErrorCode = (string) ($driveResult['error'] ?? '');
                    if ($driveErrorCode === 'token_failed' && $drive->oauthConfigured()) {
                        $drive->clearOauthToken();
                        $state['driveReady'] = false;
                        $state['driveNeedsConnect'] = true;
                        $state['errors'][] = "Jeton Drive invalide. Reconnectez Drive pour r\u{00e9}essayer.";
                    } else {
                        $driveError = $drive->errorMessage($driveResult);
                        $state['errors'][] = "Upload Drive impossible." . ($driveError !== '' ? ' ' . $driveError : " Merci de r\u{00e9}essayer.");
                    }
                } else {
                    $driveLink = (string) ($driveResult['webViewLink'] ?? '');
                    if ($driveLink === '' && !empty($driveResult['webContentLink'])) {
                        $driveLink = (string) $driveResult['webContentLink'];
                    }
                    if ($driveLink === '' && !empty($driveResult['id'])) {
                        $driveLink = 'https://drive.google.com/file/d/' . (string) $driveResult['id'] . '/view';
                    }
                    $cvPath = $driveLink !== '' ? $driveLink : null;

                    $this->applications->createPending(
                        (int) $user['id'],
                        (string) $state['title'],
                        (string) $state['bio'],
                        $cvPath,
                        $this->teachingExperienceLabel((string) $state['teaching_experience'])
                    );

                    $this->profiles->upsertTeacherProfile((int) $user['id'], [
                        'professional_title' => (string) $state['title'],
                        'short_bio' => (string) $state['bio'],
                        'portfolio_url' => (string) $state['portfolio_url'],
                        'linkedin_url' => (string) $state['linkedin_url'],
                        'cv_path' => $cvPath,
                    ]);

                    foreach ($this->users->findAdminIds() as $adminId) {
                        $this->notifications->create(
                            $adminId,
                            "Nouvelle demande d'enseignant",
                            (string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? '') . " a soumis une demande.",
                            $this->siteUrl('admin/teacher_applications')
                        );
                    }

                    $state['cvPath'] = $cvPath !== null ? $cvPath : '';
                    $state['successMessage'] = "Demande envoy\u{00e9}e. Elle sera analysée dans les heures ou les jours à suivre. Vous recevrez ensuite une notification pour la suite.";
                }
            }

            if (!empty($state['errors'])) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            } elseif ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $state['errors'][] = 'Erreur lors de la soumission.';
            if (empty($state['cvErrors'])) {
                $state['cvErrors'][] = 'Une erreur est survenue pendant l’envoi du CV.';
            }
        }

        return $state;
    }

    /**
     * @return array<int,string>
     */
    private function profileMissingFields(array $user): array
    {
        $required = [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'country' => 'pays',
        ];

        $missing = [];
        foreach ($required as $field => $label) {
            if (trim((string) ($user[$field] ?? '')) === '') {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    private function teachingExperienceLabel(string $value): string
    {
        return self::TEACHING_EXPERIENCE_LABELS[$value] ?? $value;
    }
}
