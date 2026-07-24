<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Service;
use App\Models\ApiFedapayWebhookModel;
use App\Services\Shared\FedapayService;
use Throwable;

final class FedapayWebhookService extends Service
{
    private ApiFedapayWebhookModel $model;

    public function __construct(ApiFedapayWebhookModel $model)
    {
        $this->model = $model;
    }

    public function handle(string $method, string $rawPayload, string $signatureHeader): array
    {
        $baseContext = [
            'method' => $method,
            'signature_present' => $signatureHeader !== '',
            'payload_size' => strlen($rawPayload),
        ];

        try {
            if ($method !== 'POST') {
                if (function_exists('app_log')) {
                    \app_log('warning', 'fedapay.webhook', 'Webhook FedaPay appele avec une methode non supportee.', $baseContext);
                }
                return ['status' => 405, 'payload' => ['error' => 'method_not_allowed']];
            }
            if ($rawPayload === '') {
                if (function_exists('app_log')) {
                    \app_log('warning', 'fedapay.webhook', 'Webhook FedaPay vide.', $baseContext);
                }
                return ['status' => 400, 'payload' => ['error' => 'payload_missing']];
            }

            $webhookSecret = (new FedapayService())->webhookSecret();
            if ($webhookSecret === '') {
                if (function_exists('app_log_error')) {
                    \app_log_error('fedapay.webhook', 'Secret webhook FedaPay manquant.', $baseContext);
                }
                return ['status' => 500, 'payload' => ['error' => 'missing_webhook_secret']];
            }
            $ok = $this->model->verifySignature(
                $rawPayload,
                $signatureHeader,
                $webhookSecret,
                300
            );
            if (!$ok) {
                if (function_exists('app_log')) {
                    \app_log('warning', 'fedapay.webhook', 'Signature webhook FedaPay invalide.', $baseContext + [
                        'payload_hash' => hash('sha256', $rawPayload),
                    ]);
                }
                return ['status' => 400, 'payload' => ['error' => 'signature_invalid']];
            }

            $payload = json_decode($rawPayload, true);
            if (!is_array($payload)) {
                if (function_exists('app_log_error')) {
                    \app_log_error('fedapay.webhook', 'Payload webhook FedaPay non JSON.', $baseContext + [
                        'payload_hash' => hash('sha256', $rawPayload),
                    ]);
                }
                return ['status' => 400, 'payload' => ['error' => 'invalid_json']];
            }

            $eventName = (string) ($payload['name'] ?? $payload['type'] ?? '');
            $eventKey = $this->buildEventKey($rawPayload);
            if ($eventKey !== '' && $this->model->isWebhookEventProcessed('fedapay', $eventKey)) {
                return ['status' => 200, 'payload' => ['ok' => true, 'duplicate' => true]];
            }

            $transactionId = $this->model->extractTransactionId($payload);
            if (!$transactionId) {
                if (function_exists('app_log')) {
                    \app_log('warning', 'fedapay.webhook', 'Webhook FedaPay sans transaction exploitable.', $baseContext + [
                        'event' => $eventName,
                        'event_key' => $eventKey,
                    ]);
                }
                return ['status' => 200, 'payload' => ['ok' => true]];
            }

            $paymentReference = (string) $transactionId;
            $payment = $this->model->findPaymentByReference('fedapay', $paymentReference);
            if (!$payment) {
                if (function_exists('app_log')) {
                    \app_log('warning', 'fedapay.webhook', 'Webhook FedaPay recu pour une transaction inconnue localement.', $baseContext + [
                        'event' => $eventName,
                        'event_key' => $eventKey,
                        'transaction_id' => $transactionId,
                    ]);
                }
                return ['status' => 200, 'payload' => ['ok' => true]];
            }

            $transaction = null;
            $paymentStatus = 'pending';
            $orderStatus = 'pending';

            $transactionResponse = $this->model->getTransaction($transactionId);
            if (!empty($transactionResponse['ok'])) {
                $transaction = $this->model->extractTransaction((array) ($transactionResponse['data'] ?? []));
                $providerStatus = (string) ($transaction['status'] ?? '');
                if ($providerStatus !== '') {
                    $paymentStatus = $this->model->mapPaymentStatus($providerStatus);
                    $orderStatus = $this->model->mapOrderStatus($providerStatus);
                }
            } else {
                if (function_exists('app_log_error')) {
                    \app_log_error('fedapay.webhook', 'Impossible de relire la transaction FedaPay depuis le webhook.', $baseContext + [
                        'event' => $eventName,
                        'event_key' => $eventKey,
                        'transaction_id' => $transactionId,
                        'order_id' => (int) ($payment['order_id'] ?? 0),
                        'response' => $transactionResponse,
                    ]);
                }
            }

            $this->model->upsertPayment(
                (int) ($payment['order_id'] ?? 0),
                'fedapay',
                $paymentReference,
                $paymentStatus,
                [
                    'event' => $payload,
                    'transaction' => $transaction,
                ]
            );

            $orderId = (int) ($payment['order_id'] ?? 0);
            if ($paymentStatus === 'paid') {
                if (!$this->model->finalizeOrderPayment($orderId) && function_exists('app_log_error')) {
                    \app_log_error('fedapay.webhook', 'Paiement FedaPay marque paid mais finalisation commande echouee.', [
                        'event' => $eventName,
                        'event_key' => $eventKey,
                        'transaction_id' => $transactionId,
                        'order_id' => $orderId,
                    ]);
                }
            } elseif (in_array($orderStatus, ['failed', 'canceled', 'refunded'], true)) {
                $this->model->updateOrderStatus($orderId, $orderStatus);
            }

            if ($eventKey !== '') {
                $this->model->markWebhookEventProcessed(
                    'fedapay',
                    $eventKey,
                    $eventName,
                    $paymentReference,
                    $payload
                );
            }

            return ['status' => 200, 'payload' => ['ok' => true]];
        } catch (Throwable $e) {
            if (function_exists('app_log_error')) {
                \app_log_error('fedapay.webhook', 'Webhook FedaPay en erreur.', $baseContext + [
                    'exception' => $e,
                ]);
            }
            return ['status' => 500, 'payload' => ['error' => 'webhook_processing_failed']];
        }
    }

    private function buildEventKey(string $rawPayload): string
    {
        if ($rawPayload === '') {
            return '';
        }
        return hash('sha256', $rawPayload);
    }
}
