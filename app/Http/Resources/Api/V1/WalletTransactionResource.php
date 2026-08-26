<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'status' => $this->status,
            'description' => $this->description,
            'idempotency_key' => $this->idempotency_key,
            'gateway' => $this->gateway,
            'external_transaction_id' => $this->external_transaction_id,
            'currency' => $this->currency,
            'failure_reason' => $this->failure_reason,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
