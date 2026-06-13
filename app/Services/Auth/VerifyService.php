<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Service;
use App\Models\AuthVerifyModel;
use DateTime;

final class VerifyService extends Service
{
    private AuthVerifyModel $model;

    public function __construct(AuthVerifyModel $model)
    {
        $this->model = $model;
    }

    /**
     * @return array{message:string,error:string}
     */
    public function handle(string $token): array
    {
        $message = '';
        $error = '';

        if ($token === '') {
            return [
                'message' => '',
                'error' => 'Aucun jeton de vérification n’a été fourni.',
            ];
        }

        try {
            $row = $this->model->findVerificationWithUserByHash(hash('sha256', $token));
            if (!$row) {
                $error = 'Lien de vérification invalide.';
            } elseif (!empty($row['used_at'])) {
                $error = 'Ce lien de vérification a déjà été utilisé.';
            } elseif (new DateTime((string) $row['expires_at']) < new DateTime()) {
                $error = 'Ce lien de vérification a expiré.';
            } else {
                $this->model->beginTransaction();
                $this->model->activateUser((int) ($row['user_id'] ?? 0));
                $this->model->markVerificationUsed((int) ($row['id'] ?? 0));
                $this->model->commit();
                $message = 'Adresse e-mail vérifiée. Vous pouvez maintenant vous connecter.';
            }
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $error = 'Une erreur est survenue lors de la vérification.';
        }

        return [
            'message' => $message,
            'error' => $error,
        ];
    }
}