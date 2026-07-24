<?php
declare(strict_types=1);

namespace App\Services\Student;

use App\Core\Service;
use App\Models\OrderModel;

final class OrderService extends Service
{
    private const ORDERS_PER_PAGE = 8;

    private OrderModel $orders;

    public function __construct(OrderModel $orders)
    {
        $this->orders = $orders;
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function buildPageData(int $userId, array $query = []): array
    {
        $allOrders = [];
        $orderRows = [];
        $pagination = Pagination::build(0, 1, self::ORDERS_PER_PAGE);
        $summary = $this->emptySummary();
        try {
            $allOrders = $this->orders->findByUserId($userId);
            $ordersTotal = count($allOrders);
            $page = Pagination::pageFromQuery($query, 'page');
            $pagination = Pagination::build($ordersTotal, $page, self::ORDERS_PER_PAGE);
            $orderRows = array_slice($allOrders, (int) ($pagination['offset'] ?? 0), (int) ($pagination['per_page'] ?? self::ORDERS_PER_PAGE));
            $summary = $this->summarizeOrders($allOrders);
        } catch (\Throwable $e) {
            $ordersTotal = 0;
            $orderRows = [];
            $allOrders = [];
            $summary = $this->emptySummary();
            $pagination = Pagination::build(0, 1, self::ORDERS_PER_PAGE);
        }

        $courseIds = [];
        foreach ($allOrders as $order) {
            $raw = (string) ($order['course_ids'] ?? '');
            if ($raw === '') {
                continue;
            }
            foreach (explode(',', $raw) as $cid) {
                $courseIds[] = (int) $cid;
            }
        }
        $courseIds = array_values(array_unique(array_filter($courseIds, static fn(int $id): bool => $id > 0)));
        $titles = $this->orders->findCourseTitlesByIds($courseIds);

        return [
            'orders' => $orderRows,
            'ordersCount' => $ordersTotal,
            'pagination' => $pagination,
            'titles' => $titles,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReceiptData(int $userId, int $orderId): array
    {
        try {
            $order = $this->orders->findByIdForUser($userId, $orderId);
            if (!is_array($order) || $order === []) {
                return $this->abortReceipt(404, 'Reçu indisponible', 'Commande introuvable.');
            }

            $statusRaw = strtolower(trim((string) ($order['status'] ?? '')));
            if (!$this->isReceiptableStatus($statusRaw)) {
                return $this->abortReceipt(
                    403,
                    'Reçu non disponible',
                    'Le reçu n’est disponible qu’après validation du paiement.'
                );
            }

            $items = $this->orders->findItemsByOrderId($orderId);
            $receiptItems = [];
            $subtotalXof = 0;
            foreach ($items as $row) {
                $quantity = max(1, (int) ($row['quantity'] ?? 1));
                $unitPriceXof = max(0, (int) ($row['unit_price_xof'] ?? 0));
                $lineTotalXof = $quantity * $unitPriceXof;
                $subtotalXof += $lineTotalXof;

                $courseTitle = trim((string) ($row['course_title'] ?? ''));
                if ($courseTitle === '') {
                    $courseTitle = 'Cours #' . (int) ($row['course_id'] ?? 0);
                }

                $receiptItems[] = [
                    'course_id' => (int) ($row['course_id'] ?? 0),
                    'course_title' => $courseTitle,
                    'quantity' => $quantity,
                    'unit_price_xof' => $unitPriceXof,
                    'line_total_xof' => $lineTotalXof,
                ];
            }

            $siteLogoUrl = trim($this->siteLogoUrl());

            return [
                'order' => $order,
                'items' => $receiptItems,
                'subtotal_xof' => $subtotalXof,
                'item_count' => count($receiptItems),
                'provider_label' => $this->providerLabelFor((string) ($order['provider'] ?? '')),
                'status_meta' => $this->statusMetaFor($statusRaw),
                'site_name' => defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique',
                'site_logo_url' => $siteLogoUrl !== '' ? $this->absoluteUrlFromSiteAsset($siteLogoUrl) : '',
                'support_url' => $this->siteUrl('support?space=student'),
                'orders_url' => $this->siteUrl('student/orders'),
                'courses_url' => $this->siteUrl('student/my_courses'),
                'dashboard_url' => $this->siteUrl('student/dashboard'),
            ];
        } catch (\Throwable $e) {
            return $this->abortReceipt(500, 'Reçu indisponible', 'Impossible de générer le reçu pour le moment.');
        }
    }

    /**
     * @param array<int, array<string, mixed>> $orders
     * @return array{
     *   paid_count:int,
     *   pending_count:int,
     *   failed_count:int,
     *   canceled_count:int,
     *   refunded_count:int,
     *   free_count:int,
     *   total_spent_xof:int,
     *   latest_order:array<string,mixed>|null
     * }
     */
    private function summarizeOrders(array $orders): array
    {
        $summary = $this->emptySummary();

        foreach ($orders as $orderRow) {
            $statusRow = strtolower(trim((string) ($orderRow['status'] ?? '')));
            $amountRow = max(0, (int) ($orderRow['total_xof'] ?? 0));

            if (in_array($statusRow, ['paid', 'completed', 'succeeded', 'success'], true)) {
                $summary['paid_count']++;
                $summary['total_spent_xof'] += $amountRow;
                continue;
            }

            if ($statusRow === 'pending') {
                $summary['pending_count']++;
                continue;
            }

            if ($statusRow === 'failed') {
                $summary['failed_count']++;
                continue;
            }

            if ($statusRow === 'canceled' || $statusRow === 'cancelled') {
                $summary['canceled_count']++;
                continue;
            }

            if ($statusRow === 'refunded') {
                $summary['refunded_count']++;
                continue;
            }

            if ($statusRow === 'free') {
                $summary['free_count']++;
                $summary['total_spent_xof'] += $amountRow;
            }
        }

        $summary['latest_order'] = is_array($orders[0] ?? null) ? $orders[0] : null;

        return $summary;
    }

    /**
     * @return array{
     *   paid_count:int,
     *   pending_count:int,
     *   failed_count:int,
     *   canceled_count:int,
     *   refunded_count:int,
     *   free_count:int,
     *   total_spent_xof:int,
     *   latest_order:null
     * }
     */
    private function emptySummary(): array
    {
        return [
            'paid_count' => 0,
            'pending_count' => 0,
            'failed_count' => 0,
            'canceled_count' => 0,
            'refunded_count' => 0,
            'free_count' => 0,
            'total_spent_xof' => 0,
            'latest_order' => null,
        ];
    }

    /**
     * @return array{label:string,tone:string}
     */
    private function statusMetaFor(string $status): array
    {
        return match (strtolower(trim($status))) {
            'paid', 'completed', 'succeeded', 'success' => ['label' => 'Validée', 'tone' => 'success'],
            'pending' => ['label' => 'En attente', 'tone' => 'warning'],
            'failed' => ['label' => 'Échouée', 'tone' => 'danger'],
            'cancelled', 'canceled' => ['label' => 'Annulée', 'tone' => 'danger'],
            'refunded' => ['label' => 'Remboursée', 'tone' => 'neutral'],
            'free' => ['label' => 'Gratuite', 'tone' => 'info'],
            default => ['label' => $status !== '' ? ucwords(str_replace(['_', '-'], ' ', $status)) : 'Inconnu', 'tone' => 'neutral'],
        };
    }

    private function providerLabelFor(string $provider): string
    {
        $provider = strtolower(trim($provider));
        return match ($provider) {
            'fedapay' => 'FedaPay',
            'crypto' => 'Crypto',
            'free' => 'Gratuit',
            'referral' => 'Solde de parrainage',
            default => $provider !== '' ? strtoupper($provider) : 'Non renseigné',
        };
    }

    private function isReceiptableStatus(string $status): bool
    {
        return in_array($status, ['paid', 'completed', 'succeeded', 'success', 'free'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function abortReceipt(int $status, string $title, string $message): array
    {
        return [
            'abortTitle' => $title,
            'abortMessage' => $message,
            'httpStatus' => $status,
        ];
    }

    private function absoluteUrlFromSiteAsset(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('/^https?:\\/\\//i', $url) === 1) {
            return $url;
        }

        $basePath = defined('BASE_PATH') ? trim((string) BASE_PATH) : '';
        if ($basePath !== '' && $basePath !== '/' && str_starts_with($url, $basePath . '/')) {
            $url = substr($url, strlen($basePath));
        }

        return $this->absUrl(ltrim($url, '/'));
    }
}
