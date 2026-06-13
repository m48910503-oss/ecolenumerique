<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class SharedPaymentModel extends Model
{
    private function hasOrderItemPromoColumns(): bool
    {
        return $this->columnExists('order_items', 'promo_scope')
            && $this->columnExists('order_items', 'promotion_id')
            && $this->columnExists('order_items', 'coupon_code');
    }

    private function hasOrderItemCompanyColumn(): bool
    {
        return $this->columnExists('order_items', 'company_id');
    }

    public function ensurePaymentsTable(): bool
    {
        return $this->tableExists('payments');
    }

    public function upsertPayment(
        int $orderId,
        string $provider,
        string $reference,
        string $status,
        ?string $payloadJson
    ): void {
        $stmt = $this->pdo->prepare('INSERT INTO payments (order_id, provider, reference, status, raw_payload_json, created_at, updated_at)
            VALUES (:oid, :prov, :ref, :st, :raw, NOW(), NOW())
            ON DUPLICATE KEY UPDATE order_id = VALUES(order_id), status = VALUES(status), raw_payload_json = COALESCE(VALUES(raw_payload_json), raw_payload_json), updated_at = NOW()');
        $stmt->execute([
            ':oid' => $orderId,
            ':prov' => $provider,
            ':ref' => $reference,
            ':st' => $status,
            ':raw' => $payloadJson,
        ]);
    }

    public function findPaymentByReference(string $provider, string $reference): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE provider = :prov AND reference = :ref LIMIT 1');
        $stmt->execute([
            ':prov' => $provider,
            ':ref' => $reference,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findOrderStatus(int $orderId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT status FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $orderId]);
        $status = $stmt->fetchColumn();
        if (!is_string($status) || $status === '') {
            return null;
        }
        return $status;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function insertOrder(int $userId, int $total, string $provider, string $reference): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO orders (user_id, status, total_xof, provider, reference, created_at)
            VALUES (:uid, :status, :total, :prov, :ref, NOW())');
        $stmt->execute([
            ':uid' => $userId,
            ':status' => 'pending',
            ':total' => $total,
            ':prov' => $provider,
            ':ref' => $reference,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function insertOrderItem(
        int $orderId,
        int $courseId,
        int $unitPrice,
        int $quantity = 1,
        ?int $companyId = null,
        string $promoScope = 'none',
        ?int $promotionId = null,
        ?string $couponCode = null
    ): int
    {
        $qty = max(1, $quantity);
        $companyIdValue = ($companyId ?? 0) > 0 ? (int) $companyId : null;

        if ($this->hasOrderItemPromoColumns()) {
            if ($this->hasOrderItemCompanyColumn()) {
                $stmt = $this->pdo->prepare('INSERT INTO order_items (order_id, course_id, company_id, quantity, unit_price_xof, promo_scope, promotion_id, coupon_code)
                    VALUES (:oid, :cid, :company_id, :qty, :price, :scope, :pid, :ccode)');
                $stmt->execute([
                    ':oid' => $orderId,
                    ':cid' => $courseId,
                    ':company_id' => $companyIdValue,
                    ':qty' => $qty,
                    ':price' => $unitPrice,
                    ':scope' => $promoScope,
                    ':pid' => $promotionId,
                    ':ccode' => $couponCode,
                ]);
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO order_items (order_id, course_id, quantity, unit_price_xof, promo_scope, promotion_id, coupon_code)
                    VALUES (:oid, :cid, :qty, :price, :scope, :pid, :ccode)');
                $stmt->execute([
                    ':oid' => $orderId,
                    ':cid' => $courseId,
                    ':qty' => $qty,
                    ':price' => $unitPrice,
                    ':scope' => $promoScope,
                    ':pid' => $promotionId,
                    ':ccode' => $couponCode,
                ]);
            }
        } else {
            if ($this->hasOrderItemCompanyColumn()) {
                $stmt = $this->pdo->prepare('INSERT INTO order_items (order_id, course_id, company_id, quantity, unit_price_xof)
                    VALUES (:oid, :cid, :company_id, :qty, :price)');
                $stmt->execute([
                    ':oid' => $orderId,
                    ':cid' => $courseId,
                    ':company_id' => $companyIdValue,
                    ':qty' => $qty,
                    ':price' => $unitPrice,
                ]);
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO order_items (order_id, course_id, quantity, unit_price_xof)
                    VALUES (:oid, :cid, :qty, :price)');
                $stmt->execute([
                    ':oid' => $orderId,
                    ':cid' => $courseId,
                    ':qty' => $qty,
                    ':price' => $unitPrice,
                ]);
            }
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function findOrderForUpdate(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, user_id, status FROM orders WHERE id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $orderId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateOrderStatus(int $orderId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE orders SET status = :st, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':st' => $status,
            ':id' => $orderId,
        ]);
    }

    public function fetchOrderItemsForFinalize(int $orderId): array
    {
        $hasPromoColumns = $this->hasOrderItemPromoColumns();
        $hasCompanyColumn = $this->hasOrderItemCompanyColumn();
        $promoExtra = $hasPromoColumns
            ? ', oi.promo_scope, oi.promotion_id, oi.coupon_code'
            : ', "none" AS promo_scope, NULL AS promotion_id, NULL AS coupon_code';
        $companyExtra = $hasCompanyColumn ? ', oi.company_id' : ', NULL AS company_id';
        $stmt = $this->pdo->prepare('SELECT oi.id AS order_item_id, oi.course_id, oi.unit_price_xof'
                . ', oi.quantity'
                . $promoExtra
                . $companyExtra . ',
                c.id AS id, c.teacher_id, c.price_xof, c.promo_type, c.promo_value, c.promo_start_at, c.promo_end_at,
                c.referral_override, c.referral_l2_enabled
            FROM order_items oi
            JOIN courses c ON c.id = oi.course_id
            WHERE oi.order_id = :oid');
        $stmt->execute([':oid' => $orderId]);
        return $stmt->fetchAll() ?: [];
    }

    public function insertEnrollmentIgnore(int $userId, int $courseId): void
    {
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO enrollments (user_id, course_id, status, created_at)
            VALUES (:uid, :cid, :st, NOW())');
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
            ':st' => 'active',
        ]);
    }

    public function companySeatSchemaReady(): bool
    {
        return $this->tableExists('company_course_seat_purchases');
    }

    public function insertCompanySeatPurchase(
        int $companyId,
        int $courseId,
        int $orderId,
        int $orderItemId,
        int $purchasedByUserId,
        int $seatsTotal,
        int $unitPriceXof
    ): void {
        if (
            $companyId < 1
            || $courseId < 1
            || $orderId < 1
            || $orderItemId < 1
            || $purchasedByUserId < 1
            || $seatsTotal < 1
            || !$this->companySeatSchemaReady()
        ) {
            return;
        }

        $totalXof = max(0, $unitPriceXof) * max(1, $seatsTotal);
        $stmt = $this->pdo->prepare(
            "INSERT INTO company_course_seat_purchases
                (company_id, course_id, order_id, order_item_id, purchased_by, seats_total, unit_price_xof, total_xof, status, created_at, updated_at)
             VALUES
                (:company_id, :course_id, :order_id, :order_item_id, :purchased_by, :seats_total, :unit_price_xof, :total_xof, 'active', NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                seats_total = VALUES(seats_total),
                unit_price_xof = VALUES(unit_price_xof),
                total_xof = VALUES(total_xof),
                status = 'active',
                updated_at = NOW()"
        );
        $stmt->execute([
            ':company_id' => $companyId,
            ':course_id' => $courseId,
            ':order_id' => $orderId,
            ':order_item_id' => $orderItemId,
            ':purchased_by' => $purchasedByUserId,
            ':seats_total' => max(1, $seatsTotal),
            ':unit_price_xof' => max(0, $unitPriceXof),
            ':total_xof' => $totalXof,
        ]);
    }

    public function insertCoursePromotionCompanyUse(int $promotionId, int $companyId, int $userId): void
    {
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO course_promotion_company_uses (promotion_id, company_id, user_id, used_at)
            VALUES (:pid, :cid, :uid, NOW())');
        $stmt->execute([
            ':pid' => $promotionId,
            ':cid' => $companyId,
            ':uid' => $userId,
        ]);
    }

    public function insertReferralCommission(
        int $orderId,
        int $orderItemId,
        int $courseId,
        int $teacherId,
        int $buyerId,
        int $referrerId,
        int $level,
        float $ratePercent,
        int $amountXof,
        string $status
    ): void {
        $stmt = $this->pdo->prepare('INSERT INTO referral_commissions (order_id, order_item_id, course_id, teacher_id, buyer_id, referrer_id, level, rate_percent, amount_xof, status, created_at)
            VALUES (:oid, :oiid, :cid, :tid, :bid, :rid, :lvl, :rate, :amt, :st, NOW())');
        $stmt->execute([
            ':oid' => $orderId,
            ':oiid' => $orderItemId,
            ':cid' => $courseId,
            ':tid' => $teacherId,
            ':bid' => $buyerId,
            ':rid' => $referrerId,
            ':lvl' => $level,
            ':rate' => $ratePercent,
            ':amt' => $amountXof,
            ':st' => $status,
        ]);
    }

    public function catalogAttributionSchemaReady(): bool
    {
        return $this->tableExists('catalog_order_item_attributions');
    }

    public function catalogEventSchemaReady(): bool
    {
        return $this->tableExists('catalog_event_logs');
    }

    public function insertCatalogOrderItemAttribution(
        int $orderItemId,
        int $orderId,
        int $courseId,
        int $userId,
        string $blockKey
    ): void {
        if ($orderItemId < 1 || $orderId < 1 || $courseId < 1 || $blockKey === '') {
            return;
        }
        if (!$this->catalogAttributionSchemaReady()) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO catalog_order_item_attributions (
                order_item_id, order_id, course_id, user_id, block_key, created_at
             ) VALUES (
                :order_item_id, :order_id, :course_id, :user_id, :block_key, NOW()
             )
             ON DUPLICATE KEY UPDATE
                block_key = VALUES(block_key)'
        );
        $stmt->execute([
            ':order_item_id' => $orderItemId,
            ':order_id' => $orderId,
            ':course_id' => $courseId,
            ':user_id' => $userId > 0 ? $userId : null,
            ':block_key' => $blockKey,
        ]);
    }

    public function insertCatalogPurchaseEventForOrderItem(int $orderId, int $orderItemId, int $buyerUserId): void
    {
        if ($orderItemId < 1 || $buyerUserId < 1) {
            return;
        }
        if (!$this->catalogEventSchemaReady() || !$this->catalogAttributionSchemaReady()) {
            return;
        }

        $metaJson = json_encode([
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
        ], JSON_UNESCAPED_UNICODE);
        if (!is_string($metaJson) || $metaJson === '') {
            $metaJson = null;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO catalog_event_logs (
                event_type, block_key, course_id, user_id, session_key, metadata_json, created_at
             )
             SELECT
                "purchase",
                COALESCE(ca.block_key, "unknown"),
                oi.course_id,
                :buyer_user_id,
                NULL,
                :metadata_json,
                NOW()
             FROM order_items oi
             LEFT JOIN catalog_order_item_attributions ca ON ca.order_item_id = oi.id
             WHERE oi.id = :order_item_id
             LIMIT 1'
        );
        $stmt->execute([
            ':buyer_user_id' => $buyerUserId,
            ':metadata_json' => $metaJson,
            ':order_item_id' => $orderItemId,
        ]);
    }

    public function fetchPendingFedapayPayments(int $limit, string $updatedBefore): array
    {
        $sql = 'SELECT
                p.order_id,
                p.reference,
                p.status AS payment_status,
                p.updated_at AS payment_updated_at,
                o.status AS order_status
            FROM payments p
            JOIN orders o ON o.id = p.order_id
            WHERE p.provider = :provider
              AND p.status IN ("pending", "initiated")
              AND o.status <> "paid"
              AND p.updated_at <= :updated_before
            ORDER BY p.updated_at ASC
            LIMIT :lim';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':provider', 'fedapay');
        $stmt->bindValue(':updated_before', $updatedBefore);
        $stmt->bindValue(':lim', max(1, $limit), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function webhookEventsTableExists(): bool
    {
        return $this->tableExists('payment_webhook_events');
    }

    public function isWebhookEventProcessed(string $provider, string $eventKey): bool
    {
        if (!$this->webhookEventsTableExists()) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM payment_webhook_events
             WHERE provider = :provider AND event_key = :event_key
             LIMIT 1'
        );
        $stmt->execute([
            ':provider' => $provider,
            ':event_key' => $eventKey,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function storeWebhookEventProcessed(
        string $provider,
        string $eventKey,
        string $eventName,
        string $transactionReference,
        ?string $payloadJson
    ): void {
        if (!$this->webhookEventsTableExists()) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO payment_webhook_events
                (provider, event_key, event_name, transaction_reference, payload_json, processed_at)
             VALUES
                (:provider, :event_key, :event_name, :transaction_reference, :payload_json, NOW())'
        );
        $stmt->execute([
            ':provider' => $provider,
            ':event_key' => $eventKey,
            ':event_name' => $eventName,
            ':transaction_reference' => $transactionReference !== '' ? $transactionReference : null,
            ':payload_json' => $payloadJson,
        ]);
    }
}
