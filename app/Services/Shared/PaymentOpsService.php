<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Core\Service;

use App\Models\SharedPaymentModel;
use App\Models\ReferralWalletModel;
use RuntimeException;
use Throwable;

final class PaymentOpsService extends Service
{
    private SharedPaymentModel $model;
    private ?ReferralService $referrals;
    private ?PromotionsService $promotions;
    private ?ReferralWalletModel $wallet;

    public function __construct(
        SharedPaymentModel $model,
        ?ReferralService $referrals = null,
        ?PromotionsService $promotions = null,
        ?ReferralWalletModel $wallet = null
    )
    {
        $this->model = $model;
        $this->referrals = $referrals;
        $this->promotions = $promotions;
        $this->wallet = $wallet;
    }

    public function methodEnabled(string $method): bool
    {
        $method = strtolower(trim($method));
        if ($method === 'fedapay') {
            return (new FedapayService())->isConfigured();
        }
        if ($method === 'crypto') {
            return false;
        }
        return false;
    }

    public function methodsAvailable(): array
    {
        $methods = [];
        if ($this->methodEnabled('fedapay')) {
            $methods[] = 'fedapay';
        }
        return $methods;
    }

    public function methodLabel(string $method): string
    {
        $method = strtolower(trim($method));
        if ($method === 'fedapay') {
            return 'FedaPay (Mobile Money)';
        }
        if ($method === 'crypto') {
            return defined('PAY_CRYPTO_LABEL') && PAY_CRYPTO_LABEL !== '' ? PAY_CRYPTO_LABEL : 'Crypto';
        }
        if ($method === 'free') {
            return 'Gratuit';
        }
        if ($method === 'referral') {
            return 'Solde parrainage';
        }
        return $method;
    }

    public function upsert(int $orderId, string $provider, string $reference, string $status, ?array $payload = null): void
    {
        if ($orderId < 1 || $reference === '') {
            return;
        }
        if (!$this->model->ensurePaymentsTable()) {
            return;
        }
        $resolvedStatus = strtolower(trim($status));
        $existing = $this->model->findPaymentByReference($provider, $reference);
        if (is_array($existing)) {
            $orderId = (int) ($existing['order_id'] ?? $orderId);
            $currentStatus = (string) ($existing['status'] ?? '');
            if (!$this->canAdvancePaymentStatus($currentStatus, $resolvedStatus)) {
                $resolvedStatus = $currentStatus;
            }
        }
        $payloadJson = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null;
        $this->model->upsertPayment($orderId, $provider, $reference, $resolvedStatus, $payloadJson);
    }

    public function findByReference(string $provider, string $reference): ?array
    {
        if ($reference === '') {
            return null;
        }
        if (!$this->model->ensurePaymentsTable()) {
            return null;
        }
        return $this->model->findPaymentByReference($provider, $reference);
    }

    public function createPendingOrder(int $userId, array $courses, string $provider): ?array
    {
        if ($userId < 1 || $courses === []) {
            return null;
        }

        $total = 0;
        foreach ($courses as $course) {
            $unitPrice = (int) ($course['price_final'] ?? $course['price_xof'] ?? 0);
            $quantity = max(1, (int) ($course['quantity'] ?? 1));
            $total += ($unitPrice * $quantity);
        }
        $orderRef = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));

        try {
            if (!$this->model->beginTransaction()) {
                throw new RuntimeException('Transaction impossible.');
            }
            $orderId = $this->model->insertOrder($userId, $total, $provider, $orderRef);
            foreach ($courses as $course) {
                $courseId = (int) ($course['id'] ?? 0);
                $unitPrice = (int) ($course['price_final'] ?? $course['price_xof'] ?? 0);
                $quantity = max(1, (int) ($course['quantity'] ?? 1));
                $companyId = (int) ($course['company_id'] ?? 0);
                if ($companyId < 1) {
                    $companyId = null;
                }
                $promoScope = strtolower(trim((string) ($course['promo_scope'] ?? 'none')));
                if (!in_array($promoScope, ['none', 'promotion', 'targeted', 'company', 'coupon', 'enterprise'], true)) {
                    $promoScope = 'none';
                }
                $promoIdRaw = (int) ($course['promo_id'] ?? 0);
                $promoId = $promoIdRaw > 0 ? $promoIdRaw : null;
                $couponCode = PromotionsService::normalizeCouponCode((string) ($course['coupon_code'] ?? ''));
                if ($promoScope !== 'coupon' || $couponCode === '') {
                    $couponCode = null;
                }
                $orderItemId = $this->model->insertOrderItem(
                    $orderId,
                    $courseId,
                    $unitPrice,
                    $quantity,
                    $companyId,
                    $promoScope,
                    $promoId,
                    $couponCode
                );
                $catalogBlockKey = $this->normalizeCatalogBlockKey((string) ($course['catalog_block_key'] ?? ''));
                if ($catalogBlockKey !== '') {
                    $this->model->insertCatalogOrderItemAttribution(
                        $orderItemId,
                        $orderId,
                        $courseId,
                        $userId,
                        $catalogBlockKey
                    );
                }
            }
            if ($this->model->inTransaction()) {
                $this->model->commit();
            }
            return [
                'order_id' => $orderId,
                'order_ref' => $orderRef,
                'total' => $total,
            ];
        } catch (Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            return null;
        }
    }

    public function finalizeOrderPayment(int $orderId): bool
    {
        if ($orderId < 1) {
            return false;
        }

        try {
            if (!$this->model->beginTransaction()) {
                throw new RuntimeException('Transaction impossible.');
            }

            $order = $this->model->findOrderForUpdate($orderId);
            if (!$order) {
                if ($this->model->inTransaction()) {
                    $this->model->rollBack();
                }
                return false;
            }

            $currentOrderStatus = strtolower(trim((string) ($order['status'] ?? '')));
            if (!$this->canAdvanceOrderStatus($currentOrderStatus, 'paid')) {
                if ($this->model->inTransaction()) {
                    $this->model->commit();
                }
                return true;
            }

            $alreadyPaid = $currentOrderStatus === 'paid';
            if (!$alreadyPaid) {
                $this->model->updateOrderStatus($orderId, 'paid');
            }

            $items = $this->model->fetchOrderItemsForFinalize($orderId);
            if ($items === []) {
                if ($this->model->inTransaction()) {
                    $this->model->commit();
                }
                return true;
            }

            foreach ($items as $item) {
                $companyId = (int) ($item['company_id'] ?? 0);
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                if ($companyId > 0) {
                    $this->model->insertCompanySeatPurchase(
                        $companyId,
                        (int) ($item['course_id'] ?? 0),
                        $orderId,
                        (int) ($item['order_item_id'] ?? 0),
                        (int) ($order['user_id'] ?? 0),
                        $quantity,
                        (int) ($item['unit_price_xof'] ?? 0)
                    );
                    continue;
                }

                $this->model->insertEnrollmentIgnore((int) $order['user_id'], (int) $item['course_id']);
            }

            if (!$alreadyPaid) {
                $this->applyReferralAndPromoAccounting($orderId, (int) $order['user_id'], $items);
                $this->recordCatalogPurchases($orderId, (int) $order['user_id'], $items);
            }

            if ($this->wallet instanceof ReferralWalletModel) {
                $this->wallet->updateRedemptionStatusByOrder($orderId, 'used');
            }

            if ($this->model->inTransaction()) {
                $this->model->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            return false;
        }
    }

    public function updateOrderStatus(int $orderId, string $status): void
    {
        if ($orderId < 1) {
            return;
        }
        $nextStatus = strtolower(trim($status));
        $currentStatus = $this->model->findOrderStatus($orderId);
        if (is_string($currentStatus) && $currentStatus !== '') {
            if (!$this->canAdvanceOrderStatus($currentStatus, $nextStatus)) {
                return;
            }
        }
        $this->model->updateOrderStatus($orderId, $nextStatus);
        if (
            $this->wallet instanceof ReferralWalletModel
            && in_array($nextStatus, ['failed', 'canceled', 'cancelled', 'refunded'], true)
        ) {
            $this->wallet->updateRedemptionStatusByOrder($orderId, 'cancelled');
        }
    }

    public function fetchPendingFedapayPayments(int $limit = 100, int $minAgeMinutes = 5): array
    {
        if (!$this->model->ensurePaymentsTable()) {
            return [];
        }
        $safeLimit = max(1, min(1000, $limit));
        $safeMinAge = max(0, $minAgeMinutes);
        $updatedBefore = date('Y-m-d H:i:s', time() - ($safeMinAge * 60));
        return $this->model->fetchPendingFedapayPayments($safeLimit, $updatedBefore);
    }

    public function isWebhookEventProcessed(string $provider, string $eventKey): bool
    {
        $provider = strtolower(trim($provider));
        $eventKey = trim($eventKey);
        if ($provider === '' || $eventKey === '') {
            return false;
        }
        return $this->model->isWebhookEventProcessed($provider, $eventKey);
    }

    public function markWebhookEventProcessed(
        string $provider,
        string $eventKey,
        string $eventName,
        string $transactionReference,
        ?array $payload = null
    ): void {
        $provider = strtolower(trim($provider));
        $eventKey = trim($eventKey);
        if ($provider === '' || $eventKey === '') {
            return;
        }
        $payloadJson = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null;
        $this->model->storeWebhookEventProcessed(
            $provider,
            $eventKey,
            trim($eventName),
            trim($transactionReference),
            $payloadJson
        );
    }

    private function applyReferralAndPromoAccounting(int $orderId, int $buyerId, array $items): void
    {
        $referralChain = $this->referrals instanceof ReferralService
            ? $this->referrals->getReferrerChain($buyerId)
            : ['l1' => 0, 'l2' => 0];
        $platformReferral = $this->referrals instanceof ReferralService
            ? $this->referrals->getPlatformReferralSettings()
            : ['l1_rate' => 5.0, 'l2_rate' => 2.0];

        $anyReferralCourse = false;
        foreach ($items as $item) {
            if ((int) ($item['referral_override'] ?? 0) === 1) {
                $anyReferralCourse = true;
                break;
            }
        }

        $hasReferral = $anyReferralCourse
            && (((int) ($referralChain['l1'] ?? 0) > 0) || ((int) ($referralChain['l2'] ?? 0) > 0));
        if ($hasReferral && $this->referrals instanceof ReferralService) {
            $this->referrals->ensureReferralCommissionsTable();
        }
        if ($this->promotions instanceof PromotionsService) {
            $this->promotions->ensureCoursePromotionCompanyUsesTable();
        }

        foreach ($items as $item) {
            if ($this->promotions instanceof PromotionsService) {
                if ((int) ($item['company_id'] ?? 0) > 0) {
                    continue;
                }
                $promo = $this->promotions->coursePromoData($item, null, $buyerId);
                if (($promo['promo_scope'] ?? '') === 'company') {
                    $promoId = (int) ($promo['promo_id'] ?? 0);
                    $companyId = (int) ($promo['promo_company_id'] ?? 0);
                    if ($promoId > 0 && $companyId > 0) {
                        $this->model->insertCoursePromotionCompanyUse($promoId, $companyId, $buyerId);
                    }
                }
            }

            if (!$hasReferral) {
                continue;
            }

            if ((int) ($item['company_id'] ?? 0) > 0) {
                continue;
            }

            $gross = (int) ($item['unit_price_xof'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1));
            $teacherId = (int) ($item['teacher_id'] ?? 0);
            $courseId = (int) ($item['course_id'] ?? 0);
            $orderItemId = (int) ($item['order_item_id'] ?? 0);
            $referralEnabled = (int) ($item['referral_override'] ?? 0) === 1;
            if (!$referralEnabled || $gross <= 0 || $orderItemId < 1) {
                continue;
            }

            $l1 = (int) ($referralChain['l1'] ?? 0);
            $l2 = (int) ($referralChain['l2'] ?? 0);
            $l1Rate = (float) ($platformReferral['l1_rate'] ?? 0);
            $l2Rate = (float) ($platformReferral['l2_rate'] ?? 0);

            if ($l1 > 0 && $l1 !== $buyerId && $l1Rate > 0) {
                $amount = (int) round($gross * $l1Rate / 100);
                if ($amount > 0) {
                    $this->model->insertReferralCommission(
                        $orderId,
                        $orderItemId,
                        $courseId,
                        $teacherId,
                        $buyerId,
                        $l1,
                        1,
                        $l1Rate,
                        $amount,
                        'earned'
                    );
                }
            }

            $courseL2Enabled = (int) ($item['referral_l2_enabled'] ?? 0) === 1 && $l2Rate > 0;
            if ($courseL2Enabled && $l2 > 0 && $l2 !== $l1 && $l2 !== $buyerId) {
                $amount = (int) round($gross * $l2Rate / 100);
                if ($amount > 0) {
                    $this->model->insertReferralCommission(
                        $orderId,
                        $orderItemId,
                        $courseId,
                        $teacherId,
                        $buyerId,
                        $l2,
                        2,
                        $l2Rate,
                        $amount,
                        'earned'
                    );
                }
            }
        }
    }

    /**
     * @param array<int,array<string,mixed>> $items
     */
    private function recordCatalogPurchases(int $orderId, int $buyerId, array $items): void
    {
        if ($orderId < 1 || $buyerId < 1 || $items === []) {
            return;
        }
        foreach ($items as $item) {
            $orderItemId = (int) ($item['order_item_id'] ?? 0);
            if ($orderItemId < 1) {
                continue;
            }
            $this->model->insertCatalogPurchaseEventForOrderItem($orderId, $orderItemId, $buyerId);
        }
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

    private function canAdvancePaymentStatus(string $currentStatus, string $nextStatus): bool
    {
        $currentRank = $this->paymentStatusRank($currentStatus);
        $nextRank = $this->paymentStatusRank($nextStatus);
        if ($currentRank === 0 || $nextRank === 0) {
            return true;
        }
        if ($nextRank < $currentRank) {
            return false;
        }
        return true;
    }

    private function canAdvanceOrderStatus(string $currentStatus, string $nextStatus): bool
    {
        $currentRank = $this->orderStatusRank($currentStatus);
        $nextRank = $this->orderStatusRank($nextStatus);
        if ($currentRank === 0 || $nextRank === 0) {
            return true;
        }
        if ($nextRank < $currentRank) {
            return false;
        }
        return true;
    }

    private function paymentStatusRank(string $status): int
    {
        $normalized = strtolower(trim($status));
        return match ($normalized) {
            'initiated' => 5,
            'pending' => 10,
            'failed', 'canceled', 'cancelled' => 20,
            'paid' => 30,
            'refunded' => 40,
            default => 0,
        };
    }

    private function orderStatusRank(string $status): int
    {
        $normalized = strtolower(trim($status));
        return match ($normalized) {
            'pending' => 10,
            'failed', 'canceled', 'cancelled' => 20,
            'paid' => 30,
            'refunded' => 40,
            default => 0,
        };
    }
}

