<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Core\Service;
use Throwable;

final class FedapayReconciliationService extends Service
{
    private PaymentOpsService $payments;
    private FedapayService $fedapay;

    public function __construct(PaymentOpsService $payments, FedapayService $fedapay)
    {
        $this->payments = $payments;
        $this->fedapay = $fedapay;
    }

    public function run(int $limit = 100, int $minAgeMinutes = 5): array
    {
        $items = $this->payments->fetchPendingFedapayPayments($limit, $minAgeMinutes);
        $stats = [
            'scanned' => count($items),
            'updated' => 0,
            'paid' => 0,
            'refunded' => 0,
            'failed' => 0,
            'canceled' => 0,
            'still_pending' => 0,
            'errors' => [],
        ];

        foreach ($items as $item) {
            try {
                $orderId = (int) ($item['order_id'] ?? 0);
                $reference = (string) ($item['reference'] ?? '');
                $transactionId = is_numeric($reference) ? (int) $reference : 0;
                if ($orderId < 1 || $transactionId < 1) {
                    $message = 'Reference de transaction FedaPay invalide: ' . $reference;
                    $stats['errors'][] = $message;
                    if (function_exists('app_log_error')) {
                        \app_log_error('fedapay.reconcile', $message, [
                            'order_id' => $orderId,
                            'reference' => $reference,
                        ]);
                    }
                    continue;
                }

                $remote = $this->fedapay->getTransaction($transactionId);
                if (empty($remote['ok'])) {
                    $message = 'Recuperation de la transaction FedaPay echouee: ' . $reference;
                    $stats['errors'][] = $message;
                    if (function_exists('app_log_error')) {
                        \app_log_error('fedapay.reconcile', $message, [
                            'order_id' => $orderId,
                            'reference' => $reference,
                            'transaction_id' => $transactionId,
                            'response' => $remote,
                        ]);
                    }
                    continue;
                }
                $transaction = $this->fedapay->extractTransaction((array) ($remote['data'] ?? []));
                if (!is_array($transaction)) {
                    $message = 'Transaction introuvable dans la reponse FedaPay: ' . $reference;
                    $stats['errors'][] = $message;
                    if (function_exists('app_log_error')) {
                        \app_log_error('fedapay.reconcile', $message, [
                            'order_id' => $orderId,
                            'reference' => $reference,
                            'transaction_id' => $transactionId,
                            'response' => $remote,
                        ]);
                    }
                    continue;
                }

                $providerStatus = (string) ($transaction['status'] ?? '');
                $paymentStatus = $this->fedapay->toPaymentStatus($providerStatus);
                $orderStatus = $this->fedapay->toOrderStatus($providerStatus);

                $this->payments->upsert($orderId, 'fedapay', $reference, $paymentStatus, [
                    'transaction' => $transaction,
                    'reconcile' => true,
                ]);
                $stats['updated']++;

                if ($paymentStatus === 'paid') {
                    if ($this->payments->finalizeOrderPayment($orderId)) {
                        $stats['paid']++;
                    } else {
                        $message = 'Finalisation de la commande echouee: order_id=' . $orderId;
                        $stats['errors'][] = $message;
                        if (function_exists('app_log_error')) {
                            \app_log_error('fedapay.reconcile', $message, [
                                'order_id' => $orderId,
                                'reference' => $reference,
                                'transaction_id' => $transactionId,
                            ]);
                        }
                    }
                    continue;
                }

                if ($orderStatus === 'refunded') {
                    $this->payments->updateOrderStatus($orderId, 'refunded');
                    $stats['refunded']++;
                    continue;
                }

                if ($orderStatus === 'failed') {
                    $this->payments->updateOrderStatus($orderId, 'failed');
                    $stats['failed']++;
                    continue;
                }

                if ($orderStatus === 'canceled') {
                    $this->payments->updateOrderStatus($orderId, 'canceled');
                    $stats['canceled']++;
                    continue;
                }

                $stats['still_pending']++;
            } catch (Throwable $e) {
                $message = 'Erreur inattendue pendant la reconciliation FedaPay.';
                $stats['errors'][] = $message;
                if (function_exists('app_log_error')) {
                    \app_log_error('fedapay.reconcile', $message, [
                        'item' => $item,
                        'exception' => $e,
                    ]);
                }
            }
        }

        $stats['errors_count'] = count($stats['errors']);
        return $stats;
    }
}
