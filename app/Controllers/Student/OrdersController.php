<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Models\OrderModel;
use App\Models\StudentDashboardModel;
use App\Services\Student\OrderService;
use App\Services\Web\ReceiptPdfDocumentBuilder;

final class OrdersController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('student/orders'));
        $this->disableCache();

        $service = new OrderService(new OrderModel($pdo));
        $receiptRequested = !empty($_GET['receipt']);
        $receiptOrderId = max(0, (int) ($_GET['order_id'] ?? 0));

        if ($receiptRequested) {
            $receiptData = $service->buildReceiptData((int) $user['id'], $receiptOrderId);
            if (!empty($receiptData['abortMessage'])) {
                header('Location: ' . $this->siteUrl('student/orders'));
                return;
            }

            $receiptData = array_merge($receiptData, [
                'site_logo_url' => (string) ($receiptData['site_logo_url'] ?? $siteLogoUrl),
                'user' => $user,
            ]);

            $pdfBinary = (new ReceiptPdfDocumentBuilder())->build($receiptData);
            if (is_string($pdfBinary) && $pdfBinary !== '') {
                $safeReference = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) (($receiptData['order']['reference'] ?? 'recu') ?: 'recu')) ?: 'recu';
                $filenameBase = 'recu-commande-' . strtolower(trim($safeReference, '-'));
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $filenameBase . '.pdf"');
                header('Content-Length: ' . strlen($pdfBinary));
                header('X-Content-Type-Options: nosniff');
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Expires: 0');
                echo $pdfBinary;
                return;
            }

            header('Location: ' . $this->siteUrl('student/orders'));
            return;
        }

        $data = $service->buildPageData((int) $user['id'], $_GET);
        $badges = new StudentDashboardModel($pdo);
        $unreadCount = 0;
        $supportUnreadCount = 0;
        $supportUnreadMessages = 0;
        $supportUnreadNotifications = 0;
        $activeEnrollments = 0;
        try {
            $unreadCount = $badges->countUnreadNotifications((int) $user['id']);
        } catch (\Throwable $e) {
            $unreadCount = 0;
        }
        try {
            $supportUnreadMessages = $badges->countUnreadSupportReplies((int) $user['id']);
        } catch (\Throwable $e) {
            $supportUnreadMessages = 0;
        }
        try {
            $supportUnreadNotifications = $badges->countUnreadSupportNotifications((int) $user['id']);
        } catch (\Throwable $e) {
            $supportUnreadNotifications = 0;
        }
        $supportUnreadCount = max($supportUnreadMessages, $supportUnreadNotifications);
        try {
            $activeEnrollments = $badges->countActiveEnrollments((int) $user['id']);
        } catch (\Throwable $e) {
            $activeEnrollments = 0;
        }

        $this->render('student/orders', [
            'siteLogoUrl' => $siteLogoUrl,
            'user' => $user,
            'unreadCount' => $unreadCount,
            'supportUnreadCount' => $supportUnreadCount,
            'activeEnrollments' => $activeEnrollments,
            'orders' => $data['orders'],
            'ordersCount' => (int) ($data['ordersCount'] ?? 0),
            'pagination' => $data['pagination'] ?? [],
            'titles' => $data['titles'],
            'ordersSummary' => $data['summary'] ?? [],
        ]);
    }
}

