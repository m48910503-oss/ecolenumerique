<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Service;
use App\Models\AuthForgotModel;
use DateTime;
use Throwable;

final class ForgotService extends Service
{
    private AuthForgotModel $model;

    public function __construct(AuthForgotModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(string $method, array $post): array
    {
        $message = (string) $this->flashGet('forgot_message', '');
        $debugLink = '';

        if ($method === 'POST') {
            $email = trim((string) ($post['email'] ?? ''));
            $message = "Si un compte actif correspond a cette adresse et si le service e-mail est disponible, un lien de reinitialisation sera envoye.";
            $debugLink = '';

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $user = $this->model->findActiveVerifiedUserByEmail($email);
                    if ($user && (string) ($user['status'] ?? '') === 'active' && !empty($user['email_verified_at'])) {
                        $userId = (int) ($user['id'] ?? 0);
                        if ($userId > 0) {
                            $this->model->invalidateOpenResetsForUser($userId);

                            $token = bin2hex(random_bytes(32));
                            $tokenHash = hash('sha256', $token);
                            $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
                            $this->model->createPasswordReset($userId, $tokenHash, $expiresAt);

                            $debugLink = $this->absUrl('reset?token=' . urlencode($token));
                            $mailBody = "Bonjour,\n\nVous avez demande la reinitialisation de votre mot de passe.\nLien : {$debugLink}\nCe lien expire dans 1 heure.\n\nSi vous n'etes pas a l'origine de cette demande, ignorez cet email.";
                            if (!$this->sendEmail($email, 'Reinitialisation du mot de passe', $mailBody) && function_exists('app_log_error')) {
                                \app_log_error('auth.forgot', 'Email de reinitialisation non envoye.', [
                                    'user_id' => $userId,
                                    'email' => $email,
                                    'mail_error' => function_exists('last_mail_error') ? \last_mail_error() : [],
                                ]);
                            }
                        }
                    }
                } catch (Throwable $e) {
                    if (function_exists('app_log_error')) {
                        \app_log_error('auth.forgot', 'Flux mot de passe oublie en erreur.', [
                            'email' => $email,
                            'exception' => $e,
                        ]);
                    }
                }
            }

            $this->flashSet('forgot_message', $message);
            return [
                'redirectTo' => $this->siteUrl('forgot'),
                'view' => [],
            ];
        }

        return [
            'redirectTo' => null,
            'view' => [
                'message' => $message,
                'debugLink' => $debugLink,
            ],
        ];
    }
}
