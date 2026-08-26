<?php

namespace App\Http\Controllers\Api\V1\Wallet;

use App\Domain\Wallet\Services\WalletService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    use ApiResponse;

    public function __construct(protected WalletService $walletService) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        $transaction = $this->walletService->deposit(
            $request->user(),
            (float) $validated['amount'],
            $validated['description'] ?? null,
            $validated['idempotency_key'] ?? null,
        );

        if ($transaction->status === 'failed') {
            return $this->success(
                data: new WalletTransactionResource($transaction),
                message: 'Deposit failed. '.($transaction->failure_reason ?? 'Payment gateway declined the charge.'),
                code: 422,
            );
        }

        return $this->created(
            data: new WalletTransactionResource($transaction),
            message: 'Deposit successful.',
        );
    }
}
