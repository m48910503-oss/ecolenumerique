<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Service;

use App\Models\ApiCommerceModel;
use App\Services\Shared\PromotionsService;
use App\Services\Shared\ReferralService;

final class CommerceService extends Service
{
    private ApiCommerceModel $model;
    private ReferralService $referrals;
    private PromotionsService $promotions;

    public function __construct(
        ApiCommerceModel $model,
        ReferralService $referrals,
        PromotionsService $promotions
    )
    {
        $this->model = $model;
        $this->referrals = $referrals;
        $this->promotions = $promotions;
    }

    public function handleCart(string $method, string $action, array $post): array
    {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (!isset($_SESSION['cart_meta']) || !is_array($_SESSION['cart_meta'])) {
            $_SESSION['cart_meta'] = [];
        }

        if ($method === 'POST') {
            $courseId = (int) ($post['course_id'] ?? 0);
            if ($courseId <= 0) {
                return ['status' => 400, 'payload' => ['error' => 'course_id manquant']];
            }
            $courseKey = (string) $courseId;
            $originBlock = $this->normalizeCatalogBlockKey((string) ($post['origin_block'] ?? ''));

            if ($action === 'add') {
                if (!in_array($courseId, $_SESSION['cart'], true)) {
                    $_SESSION['cart'][] = $courseId;
                }
                if ($originBlock !== '') {
                    $_SESSION['cart_meta'][$courseKey] = [
                        'origin_block' => $originBlock,
                        'updated_at' => time(),
                    ];
                }
            } elseif ($action === 'remove') {
                $_SESSION['cart'] = array_values(array_filter(
                    $_SESSION['cart'],
                    static fn($id): bool => (int) $id !== $courseId
                ));
                unset($_SESSION['cart_meta'][$courseKey]);
            }
        }

        return ['status' => 200, 'payload' => ['count' => count($_SESSION['cart'])]];
    }

    private function normalizeCatalogBlockKey(string $raw): string
    {
        $value = strtolower(trim($raw));
        if ($value === '') {
            return '';
        }
        $value = preg_replace('/[^a-z0-9_-]/', '-', $value);
        $value = trim((string) $value, '-_');
        if ($value === '') {
            return '';
        }
        if (strlen($value) > 64) {
            $value = substr($value, 0, 64);
        }
        return $value;
    }

    public function handleRating(array $user, string $method, array $post): array
    {
        if ($method !== 'POST') {
            return ['status' => 405, 'payload' => ['error' => 'method_not_allowed']];
        }

        $courseId = (int) ($post['course_id'] ?? 0);
        $rating = (int) ($post['rating'] ?? 0);
        $comment = trim((string) ($post['comment'] ?? ''));

        if ($courseId < 1 || $rating < 1 || $rating > 5) {
            return ['status' => 400, 'payload' => ['error' => 'parametres_invalides']];
        }

        $course = $this->model->findCourseForRating($courseId);
        if (!$course) {
            return ['status' => 404, 'payload' => ['error' => 'cours_introuvable']];
        }

        if (!$this->model->hasActiveEnrollment((int) $user['id'], $courseId)) {
            return ['status' => 403, 'payload' => ['error' => 'non_inscrit']];
        }

        try {
            $pdo = $this->pdo();
            $pdo->beginTransaction();

            $this->model->upsertCourseRating(
                $courseId,
                (int) $user['id'],
                $rating,
                $comment !== '' ? $comment : null
            );
            $avg = $this->model->fetchCourseRatingStats($courseId);

            if (!empty($course['teacher_id'])) {
                $this->model->createNotification(
                    (int) $course['teacher_id'],
                    'Nouvel avis',
                    (string) ($user['first_name'] ?? 'Un utilisateur')
                        . ' a note "'
                        . (string) ($course['title'] ?? 'Cours')
                        . '" : '
                        . $rating
                        . '/5',
                    $this->siteUrl('course?id=' . $courseId)
                );
            }

            $pdo->commit();

            return [
                'status' => 200,
                'payload' => [
                    'ok' => true,
                    'avg' => round((float) ($avg['avg_rating'] ?? 0), 2),
                    'count' => (int) ($avg['cnt'] ?? 0),
                ],
            ];
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => 500, 'payload' => ['error' => 'erreur_serveur']];
        }
    }

    public function handleReferralValidate(array $user, string $method, array $post): array
    {
        if ($method !== 'POST') {
            return ['status' => 405, 'payload' => ['ok' => false, 'error' => "M\u{00e9}thode non autoris\u{00e9}e."]];
        }

        $courseId = (int) ($post['course_id'] ?? 0);
        $rawCode = trim((string) ($post['code'] ?? ''));
        if ($courseId <= 0 || $rawCode === '') {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => "Code de parrainage manquant."]];
        }

        $parts = explode('.', $rawCode, 2);
        $courseToken = '';
        $refCode = $rawCode;
        if (count($parts) === 2) {
            if ($parts[0] === '' || $parts[1] === '') {
                return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'Format incomplet: CODECOURS.CODEPARRAIN.']];
            }
            $courseToken = $parts[0];
            $refCode = $parts[1];
        }

        $course = $this->model->findApprovedCourseForReferral($courseId);
        if (!$course) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'Cours introuvable.']];
        }

        if (empty($course['share_token'])) {
            $course['share_token'] = $this->promotions->ensureCourseShareToken($courseId);
        }
        $shareToken = (string) ($course['share_token'] ?? '');
        if ($courseToken !== '') {
            if ($shareToken === '' || !hash_equals($shareToken, $courseToken)) {
                return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'Ce code ne correspond pas a ce cours.']];
            }
        }

        $referrerId = $this->referrals->getUserIdByReferralCode($refCode);
        if (!$referrerId) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => "Code de parrainage invalide."]];
        }

        if ((int) $referrerId === (int) $user['id']) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => "Le code de parrainage ne peut pas \u{00ea}tre votre propre code."]];
        }

        $teacherId = (int) ($course['teacher_id'] ?? 0);
        if ((int) $referrerId !== $teacherId) {
            $eligible = $this->model->hasActiveEnrollment((int) $referrerId, $courseId);
            if (!$eligible) {
                return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'Le parrain n est pas inscrit a ce cours.']];
            }
        }

        return ['status' => 200, 'payload' => ['ok' => true, 'message' => 'Code valide.']];
    }
}

