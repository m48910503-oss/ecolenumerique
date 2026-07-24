<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Service;
use App\Models\AuthRegisterModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\ReferralService;
use DateTime;
use Throwable;

final class RegisterService extends Service
{
    private AuthRegisterModel $model;
    private CompanySchemaService $companies;
    private ReferralService $referrals;

    public function __construct(
        AuthRegisterModel $model,
        CompanySchemaService $companies,
        ReferralService $referrals
    ) {
        $this->model = $model;
        $this->companies = $companies;
        $this->referrals = $referrals;
    }

    /**
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(string $method, array $post): array
    {
        $errors = [];
        $successMessage = (string) $this->flashGet('success', '');
        $verificationLink = (string) $this->flashGet('verification_link', '');
        $referralInput = trim((string) ($_SESSION['referral_code'] ?? ''));
        $referralDetected = $referralInput !== '';
        $accountType = 'personal';
        $companyName = '';
        $uiStep = 1;
        $old = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'terms' => false,
            'role_teacher' => false,
        ];

        if ($method === 'POST') {
            $first = trim((string) ($post['first_name'] ?? ''));
            $last = trim((string) ($post['last_name'] ?? ''));
            $email = trim((string) ($post['email'] ?? ''));
            $password = (string) ($post['password'] ?? '');
            $confirmPassword = (string) ($post['password_confirm'] ?? '');
            $wantTeacher = !empty($post['role_teacher']);
            $accountType = trim((string) ($post['account_type'] ?? 'personal'));
            if (!in_array($accountType, ['personal', 'company'], true)) {
                $accountType = 'personal';
            }
            $wantCompany = $accountType === 'company';
            if ($wantCompany) {
                $wantTeacher = false;
            }
            $companyName = trim((string) ($post['company_name'] ?? ''));
            $terms = !empty($post['terms']);
            $referralInput = trim((string) ($post['referral_code'] ?? ''));
            $referralDetected = $referralInput !== '';
            $uiStep = (int) ($post['_ui_step'] ?? 1) === 2 ? 2 : 1;
            $referrerId = null;

            $old = [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'terms' => $terms,
                'role_teacher' => $wantTeacher,
            ];

            if ($first === '' || $last === '') {
                $errors[] = 'Le prénom et le nom sont obligatoires.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse e-mail invalide.';
            }
            if (strlen($password) < 8) {
                $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Le mot de passe doit contenir au moins une lettre majuscule.';
            }
            if (!preg_match('/\d/', $password)) {
                $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
            }
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
            }
            if ($password !== $confirmPassword) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }
            if (!$terms) {
                $errors[] = 'Vous devez accepter les CGU et la politique de confidentialité.';
            }
            if ($wantCompany && $companyName === '') {
                $errors[] = 'Le nom de l’entreprise est obligatoire.';
            }

            if ($referralInput !== '') {
                $referralCode = $referralInput;
                $parts = explode('.', $referralInput, 2);
                $referralOk = true;
                if (count($parts) === 2) {
                    $courseToken = trim($parts[0]);
                    $referralCode = trim($parts[1]);
                    if ($courseToken === '' || $referralCode === '') {
                        $errors[] = 'Code de parrainage incomplet. Format attendu : CODECOURS.CODEPARRAIN ou CODEPARRAIN.';
                        $referralOk = false;
                    }
                }
                if ($referralOk) {
                    $referrerId = $this->referrals->getUserIdByReferralCode($referralCode);
                    if (!$referrerId) {
                        $errors[] = 'Code de parrainage invalide.';
                    }
                }
            }

            if ($errors === [] && $this->model->emailExists($email)) {
                $errors[] = 'Un compte existe déjà avec cette adresse e-mail.';
            }
            if ($errors === [] && $wantCompany && !$this->companies->ensureCoreSchema()) {
                $errors[] = 'La création d’un espace entreprise est indisponible pour le moment.';
            }

            if ($errors === []) {
                $userId = 0;
                try {
                    $this->model->beginTransaction();

                    $roles = ['student'];
                    if ($wantTeacher) {
                        $roles[] = 'teacher_candidate';
                    }
                    if ($wantCompany) {
                        $roles[] = 'company_admin';
                    }

                    $userId = $this->model->createUser(
                        $first,
                        $last,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        'pending_email',
                        $roles
                    );

                    if ($wantCompany) {
                        $companyId = $this->model->createCompany($companyName, $userId, 'active');
                        $this->model->createCompanyMember($companyId, $userId, 'owner', 'active');
                    }

                    $this->referrals->ensureUserReferralCode($userId);
                    if ($referrerId && $referrerId !== $userId) {
                        $this->referrals->setUserReferrer($userId, $referrerId);
                    }
                    unset($_SESSION['referral_code'], $_SESSION['referral_at'], $_SESSION['referral_course_token']);

                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $expiresAt = (new DateTime('+1 day'))->format('Y-m-d H:i:s');
                    $this->model->createEmailVerification($userId, $tokenHash, $expiresAt);

                    $this->model->commit();

                    $verificationLink = $this->absUrl('verify?token=' . urlencode($token));
                    $mailBody = "Bonjour {$first},\n\nMerci de confirmer votre adresse e-mail : {$verificationLink}\n\nSi vous n’êtes pas à l’origine de cette inscription, ignorez cet e-mail.";
                    if (!$this->sendEmail($email, 'Vérification de votre adresse e-mail', $mailBody)) {
                        if (function_exists('app_log_error')) {
                            \app_log_error('auth.register', 'E-mail de vérification non envoyé après création du compte.', [
                                'user_id' => $userId,
                                'email' => $email,
                                'mail_error' => function_exists('last_mail_error') ? \last_mail_error() : [],
                            ]);
                        }

                        $this->flashSet('error', "Compte créé, mais l’e-mail de vérification n’a pas pu être envoyé. Réessayez plus tard ou contactez l’administration.");

                        return [
                            'redirectTo' => $this->siteUrl('register'),
                            'view' => [],
                        ];
                    }

                    $successMessage = 'Compte créé. Vérifiez votre adresse e-mail avant de vous connecter.';
                    if ($wantTeacher) {
                        $successMessage .= ' Le parcours enseignant reste soumis à validation avant l’accès au tableau de bord enseignant.';
                    }
                } catch (Throwable $e) {
                    if ($this->model->inTransaction()) {
                        $this->model->rollBack();
                    }
                    if (function_exists('app_log_error')) {
                        \app_log_error('auth.register', 'Création de compte échouée.', [
                            'email' => $email,
                            'account_type' => $accountType,
                            'exception' => $e,
                        ]);
                    }
                    $errors[] = 'Une erreur est survenue lors de la création du compte.';
                }
            }

            if ($errors === [] && $successMessage !== '') {
                $this->flashSet('success', $successMessage);
                if (
                    $verificationLink !== ''
                    && function_exists('is_local_environment')
                    && \is_local_environment()
                ) {
                    $this->flashSet('verification_link', $verificationLink);
                }
                return [
                    'redirectTo' => $this->siteUrl('register'),
                    'view' => [],
                ];
            }
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'successMessage' => $successMessage,
                'verificationLink' => $verificationLink,
                'referralInput' => $referralInput,
                'referralDetected' => $referralDetected,
                'accountType' => $accountType,
                'companyName' => $companyName,
                'old' => $old,
                'uiStep' => $uiStep,
            ],
        ];
    }
}
