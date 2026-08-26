<?php

namespace App\Domain\Wallet\Services;

use App\Domain\User\Models\User;
use App\Domain\Wallet\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class WalletService
{
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway
    ) {}

    /**
     * Two-phase deposit:
     * 1) pending ledger row (idempotent)
     * 2) gateway charge
     * 3) credit wallet only on success
     *
     * On gateway failure the transaction is marked "failed" and RETURNED (never
     * thrown) so the failed ledger row is committed and the idempotency key is
     * consumed — retrying with the same key cannot double-credit.
     */
    public function deposit(
        User $user,
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null,
        array $gatewayDetails = []
    ): WalletTransaction {
        if ($amount < 1) {
            throw ValidationException::withMessages([
                'amount' => 'Deposit amount must be at least 1.',
            ]);
        }

        $idempotencyKey = $idempotencyKey ?: (string) Str::uuid();

        // Fast idempotent replay outside the write path.
        $existing = $this->findDepositByIdempotency($user, $idempotencyKey);
        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $amount, $description, $idempotencyKey, $gatewayDetails) {
            $wallet = $user->wallet()->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            // Re-check under lock to close race windows.
            $existing = $wallet->transactions()
                ->where('type', 'deposit')
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            /** @var WalletTransaction $transaction */
            $transaction = $wallet->transactions()->create([
                'type' => 'deposit',
                'amount' => $amount,
                'status' => 'pending',
                'description' => $description ?? 'Wallet deposit',
                'idempotency_key' => $idempotencyKey,
                'gateway' => $this->paymentGateway->getGatewayName(),
                'currency' => $gatewayDetails['currency'] ?? 'IRR',
            ]);

            try {
                $charge = $this->paymentGateway->charge($amount, array_merge($gatewayDetails, [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'idempotency_key' => $idempotencyKey,
                    'reference' => 'wallet_deposit_'.$transaction->id,
                    'currency' => $transaction->currency,
                ]));
            } catch (Throwable $e) {
                $transaction->update([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);

                return $transaction->refresh();
            }

            if (($charge['success'] ?? false) !== true) {
                $transaction->update([
                    'status' => 'failed',
                    'external_transaction_id' => $charge['transaction_id'] ?? null,
                    'failure_reason' => $charge['message'] ?? 'Payment failed.',
                ]);

                return $transaction->refresh();
            }

            $externalId = $charge['transaction_id'] ?? null;
            if (! is_string($externalId) || $externalId === '') {
                $transaction->update([
                    'status' => 'failed',
                    'failure_reason' => 'Gateway returned no transaction id.',
                ]);

                return $transaction->refresh();
            }

            // Credit only after confirmed charge.
            $wallet->increment('balance', $amount);

            $transaction->update([
                'status' => 'success',
                'external_transaction_id' => $externalId,
                'gateway' => $charge['gateway'] ?? $this->paymentGateway->getGatewayName(),
                'failure_reason' => null,
            ]);

            return $transaction->refresh();
        });
    }

    public function withdraw(User $user, float $amount, ?string $description = null): void
    {
        DB::transaction(function () use ($user, $amount, $description) {
            $wallet = $user->wallet()->lockForUpdate()->firstOrFail();

            if ($wallet->balance < $amount) {
                throw ValidationException::withMessages([
                    'wallet' => 'Insufficient wallet balance.',
                ]);
            }

            $wallet->decrement('balance', $amount);

            $wallet->transactions()->create([
                'type' => 'withdraw',
                'amount' => $amount,
                'status' => 'success',
                'description' => $description,
            ]);
        });
    }

    public function payForBooking(User $user, float $amount, int $bookingId): void
    {
        DB::transaction(function () use ($user, $amount, $bookingId) {
            $wallet = $user->wallet()->lockForUpdate()->firstOrFail();

            if ($wallet->balance < $amount) {
                throw ValidationException::withMessages([
                    'wallet' => 'Insufficient balance for booking payment.',
                ]);
            }

            $wallet->decrement('balance', $amount);

            $wallet->transactions()->create([
                'type' => 'payment',
                'amount' => $amount,
                'reference_type' => 'Booking',
                'reference_id' => $bookingId,
                'status' => 'success',
                'description' => 'Payment for booking #'.$bookingId,
            ]);
        });
    }

    public function refundForCancelledBooking(User $user, float $amount, int $bookingId): void
    {
        DB::transaction(function () use ($user, $amount, $bookingId) {
            $wallet = $user->wallet()->lockForUpdate()->firstOrFail();

            $wallet->increment('balance', $amount);

            $wallet->transactions()->create([
                'type' => 'refund',
                'amount' => $amount,
                'reference_type' => 'Booking',
                'reference_id' => $bookingId,
                'status' => 'success',
                'description' => 'Refund for cancelled booking #'.$bookingId,
            ]);
        });
    }

    protected function findDepositByIdempotency(User $user, string $idempotencyKey): ?WalletTransaction
    {
        $wallet = $user->wallet()->first();
        if ($wallet === null) {
            return null;
        }

        return $wallet->transactions()
            ->where('type', 'deposit')
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }
}
