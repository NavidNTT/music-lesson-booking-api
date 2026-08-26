<?php

namespace App\Domain\Wallet\Services;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Local/dev payment gateway.
 * Simulates an external PSP without real money movement.
 * Swap this binding for Stripe/PayPal in production.
 */
class LocalWalletPaymentGateway implements PaymentGatewayInterface
{
    public function charge(float $amount, array $details): array
    {
        if ($amount <= 0) {
            throw new RuntimeException('Charge amount must be greater than zero.');
        }

        // Optional test hook: force failure for negative-path tests.
        if (($details['force_failure'] ?? false) === true) {
            return [
                'success' => false,
                'transaction_id' => null,
                'status' => 'failed',
                'gateway' => $this->getGatewayName(),
                'message' => $details['failure_reason'] ?? 'Local gateway charge failed.',
                'raw' => $details,
            ];
        }

        $externalId = 'local_'.Str::ulid()->toBase32();

        return [
            'success' => true,
            'transaction_id' => $externalId,
            'status' => 'succeeded',
            'gateway' => $this->getGatewayName(),
            'amount' => round($amount, 2),
            'currency' => $details['currency'] ?? 'IRR',
            'message' => 'Local charge succeeded.',
            'raw' => [
                'user_id' => $details['user_id'] ?? null,
                'idempotency_key' => $details['idempotency_key'] ?? null,
                'reference' => $details['reference'] ?? null,
            ],
        ];
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        if ($transactionId === '') {
            throw new RuntimeException('Refund requires a valid external transaction id.');
        }

        return [
            'success' => true,
            'transaction_id' => 'refund_'.Str::ulid()->toBase32(),
            'original_transaction_id' => $transactionId,
            'status' => 'refunded',
            'gateway' => $this->getGatewayName(),
            'amount' => $amount,
            'message' => 'Local refund succeeded.',
        ];
    }

    public function getGatewayName(): string
    {
        return 'local_wallet';
    }
}
