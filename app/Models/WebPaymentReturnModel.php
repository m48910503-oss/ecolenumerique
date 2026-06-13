<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Shared\FedapayService;
use App\Services\Shared\PaymentOpsService;

final class WebPaymentReturnModel
{
    private PaymentOpsService $paymentOps;
    private FedapayService $fedapay;

    public function __construct(PaymentOpsService $paymentOps, FedapayService $fedapay)
    {
        $this->paymentOps = $paymentOps;
        $this->fedapay = $fedapay;
    }

    public function fetchTransaction(int $transactionId): array
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

    public function findPayment(string $provider, string $reference): ?array
    {
        return $this->paymentOps->findByReference($provider, $reference);
    }

    public function upsertPayment(int $orderId, string $provider, string $reference, string $status, array $payload): void
    {
        $this->paymentOps->upsert($orderId, $provider, $reference, $status, $payload);
    }

    public function finalizeOrder(int $orderId): bool
    {
        return $this->paymentOps->finalizeOrderPayment($orderId);
    }

    public function setOrderStatus(int $orderId, string $status): void
    {
        $this->paymentOps->updateOrderStatus($orderId, $status);
    }
}
