<?php

namespace App\Services\Payment;

interface PaymentProviderInterface
{
    /**
     * Collect payment from customer
     */
    public function collect(array $data): array;

    /**
     * Send payout to recipient (driver)
     */
    public function payout(array $data): array;

    /**
     * Get transaction status
     */
    public function getTransactionStatus(string $transactionId): array;

    /**
     * Handle webhook callback
     */
    public function handleWebhook(array $payload): array;

    /**
     * Get provider name
     */
    public function getProviderName(): string;

    /**
     * Check if provider is available
     */
    public function isAvailable(): bool;
}
