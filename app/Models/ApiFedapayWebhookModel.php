<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Shared\FedapayService;
use App\Services\Shared\PaymentOpsService;

final class ApiFedapayWebhookModel
{
    private PaymentOpsService $paymentOps;
    private FedapayService $fedapay;

    public function __construct(PaymentOpsService $paymentOps, FedapayService $fedapay)
    {
        $this->paymentOps = $paymentOps;
        $this->fedapay = $fedapay;
    }

    public function verifySignature(string $payload, string $signatureHeader, string $secret, int $tolerance = 300): bool
    {
        return $this->fedapay->verifySignature($payload, $signatureHeader, $secret, $tolerance);
    }

    public function extractTransactionId(array $eventPayload): ?int
    {
        return $this->fedapay->extractEventTransactionId($eventPayload);
    }

    public function findPaymentByReference(string $provider, string $reference): ?array
    {
        return $this->paymentOps->findByReference($provider, $reference);
    }

    public function getTransaction(int $transactionId): array
    {
        return $this->fedapay->getTransaction($transactionId);
    }

    public function extractTransaction(array $payload): ?array
    {
        return $this->fedapay->extractTransaction($payload);
    }

    public function mapPaymentStatus(string $providerStatus): string
    {
        return $this->fedapay->toPaymentStatus($providerStatus);
    }

    public function mapOrderStatus(string $providerStatus): string
    {
        return $this->fedapay->toOrderStatus($providerStatus);
    }

    public function upsertPayment(
        int $orderId,
        string $provider,
        string $reference,
        string $status,
        ?array $payload = null
    ): void {
        $this->paymentOps->upsert($orderId, $provider, $reference, $status, $payload);
    }

    public function finalizeOrderPayment(int $orderId): bool
    {
        return $this->paymentOps->finalizeOrderPayment($orderId);
    }

    public function updateOrderStatus(int $orderId, string $status): void
    {
        $this->paymentOps->updateOrderStatus($orderId, $status);
    }

    public function isWebhookEventProcessed(string $provider, string $eventKey): bool
    {
        return $this->paymentOps->isWebhookEventProcessed($provider, $eventKey);
    }

    public function markWebhookEventProcessed(
        string $provider,
        string $eventKey,
        string $eventName,
        string $transactionReference,
        ?array $payload = null
    ): void {
        $this->paymentOps->markWebhookEventProcessed(
            $provider,
            $eventKey,
            $eventName,
            $transactionReference,
            $payload
        );
    }
}
