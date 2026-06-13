<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;
use App\Models\WebPaymentReturnModel;
use Throwable;

final class PaymentReturnService extends Service
{
    private WebPaymentReturnModel $payments;

    public function __construct(WebPaymentReturnModel $payments)
    {
        $this->payments = $payments;
    }

    public function processTransaction(int $transactionId): array
    {
        $errors = [];
        $success = '';
        $completed = false;

        try {
            if ($transactionId < 1) {
                if (function_exists('app_log')) {
                    \app_log('warning', 'fedapay.return', 'Retour FedaPay sans transaction exploitable.', [
                        'transaction_id' => $transactionId,
                    ]);
                }
                $errors[] = 'Transaction introuvable.';
                return ['errors' => $errors, 'success' => $success, 'completed' => $completed];
            }

            $remote = $this->payments->fetchTransaction($transactionId);
            if (empty($remote['ok'])) {
                if (function_exists('app_log_error')) {
                    \app_log_error('fedapay.return', 'Impossible de relire une transaction FedaPay au retour navigateur.', [
                        'transaction_id' => $transactionId,
                        'response' => $remote,
                    ]);
                }
                $errors[] = 'Paiement introuvable.';
                return ['errors' => $errors, 'success' => $success, 'completed' => $completed];
            }

            $transaction = $this->payments->extractTransaction((array) ($remote['data'] ?? []));
            if (!$transaction) {
                if (function_exists('app_log_error')) {
                    \app_log_error('fedapay.return', 'Reponse FedaPay invalide au retour navigateur.', [
                        'transaction_id' => $transactionId,
                        'response' => $remote,
                    ]);
                }
                $errors[] = 'Reponse paiement invalide.';
                return ['errors' => $errors, 'success' => $success, 'completed' => $completed];
            }

            $providerStatus = (string) ($transaction['status'] ?? '');
            $paymentStatus = $this->payments->mapPaymentStatus($providerStatus);
            $orderStatus = $this->payments->mapOrderStatus($providerStatus);
            $paymentReference = (string) $transactionId;
            $payment = $this->payments->findPayment('fedapay', $paymentReference);

            if (!$payment) {
                if (function_exists('app_log_error')) {
                    \app_log_error('fedapay.return', 'Retour FedaPay pour un paiement inconnu localement.', [
                        'transaction_id' => $transactionId,
                        'provider_status' => $providerStatus,
                    ]);
                }
                $errors[] = 'Commande introuvable pour ce paiement.';
                return ['errors' => $errors, 'success' => $success, 'completed' => $completed];
            }

            $orderId = (int) ($payment['order_id'] ?? 0);
            $this->payments->upsertPayment($orderId, 'fedapay', $paymentReference, $paymentStatus, [
                'transaction' => $transaction,
                'return' => true,
            ]);

            if ($paymentStatus === 'paid') {
                if ($this->payments->finalizeOrder($orderId)) {
                    $_SESSION['cart'] = [];
                    $_SESSION['cart_meta'] = [];
                    $_SESSION['cart_coupon_code'] = '';
                    $_SESSION['cart_company_seats'] = [];
                    $success = 'Paiement reussi. Commande finalisee.';
                    $completed = true;
                } else {
                    if (function_exists('app_log_error')) {
                        \app_log_error('fedapay.return', 'Paiement FedaPay paid mais finalisation commande echouee au retour.', [
                            'transaction_id' => $transactionId,
                            'order_id' => $orderId,
                        ]);
                    }
                    $errors[] = 'Paiement valide, mais inscription en echec.';
                }
                return ['errors' => $errors, 'success' => $success, 'completed' => $completed];
            }

            if ($orderStatus === 'refunded') {
                $this->payments->setOrderStatus($orderId, 'refunded');
                if (function_exists('app_log')) {
                    \app_log('warning', 'fedapay.return', 'Paiement FedaPay rembourse au retour navigateur.', [
                        'transaction_id' => $transactionId,
                        'order_id' => $orderId,
                        'provider_status' => $providerStatus,
                    ]);
                }
                $errors[] = 'Paiement rembourse.';
                return ['errors' => $errors, 'success' => $success, 'completed' => $completed];
            }

            if ($orderStatus === 'failed' || $orderStatus === 'canceled') {
                $this->payments->setOrderStatus($orderId, $orderStatus);
                if (function_exists('app_log')) {
                    \app_log('warning', 'fedapay.return', 'Paiement FedaPay non valide au retour navigateur.', [
                        'transaction_id' => $transactionId,
                        'order_id' => $orderId,
                        'provider_status' => $providerStatus,
                        'order_status' => $orderStatus,
                    ]);
                }
                $errors[] = 'Paiement non valide. Statut : ' . ($providerStatus !== '' ? $providerStatus : 'inconnu');
                return ['errors' => $errors, 'success' => $success, 'completed' => $completed];
            }

            $success = 'Paiement en attente de confirmation.';
            return ['errors' => $errors, 'success' => $success, 'completed' => $completed];
        } catch (Throwable $e) {
            if (function_exists('app_log_error')) {
                \app_log_error('fedapay.return', 'Retour paiement FedaPay en erreur.', [
                    'transaction_id' => $transactionId,
                    'exception' => $e,
                ]);
            }
            return ['errors' => ['Erreur lors de la verification du paiement.'], 'success' => '', 'completed' => false];
        }
    }
}
