<?php

namespace Tests\Feature\Wallet;

use App\Domain\User\Models\User;
use App\Domain\Wallet\Models\Wallet;
use App\Domain\Wallet\Services\LocalWalletPaymentGateway;
use App\Domain\Wallet\Services\PaymentGatewayInterface;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_deposit_via_local_gateway(): void
    {
        $user = User::factory()->create(['role' => UserRole::Student]);
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/wallet/deposit', [
            'amount' => 150000,
            'description' => 'Test top-up',
            'idempotency_key' => 'dep-key-1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('data.amount', 150000)
            ->assertJsonPath('data.gateway', 'local_wallet');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 150000,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'type' => 'deposit',
            'amount' => 150000,
            'status' => 'success',
            'idempotency_key' => 'dep-key-1',
        ]);
    }

    public function test_duplicate_idempotency_key_does_not_double_credit(): void
    {
        $user = User::factory()->create(['role' => UserRole::Student]);
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);

        Sanctum::actingAs($user);

        $payload = [
            'amount' => 50000,
            'idempotency_key' => 'same-key-once',
        ];

        $this->postJson('/api/v1/wallet/deposit', $payload)->assertCreated();
        $this->postJson('/api/v1/wallet/deposit', $payload)->assertCreated();

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 50000,
        ]);

        $this->assertEquals(
            1,
            $user->wallet->transactions()->where('idempotency_key', 'same-key-once')->count()
        );
    }

    public function test_failed_gateway_charge_does_not_credit_wallet(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return new class extends LocalWalletPaymentGateway {
                public function charge(float $amount, array $details): array
                {
                    return parent::charge($amount, array_merge($details, [
                        'force_failure' => true,
                        'failure_reason' => 'Card declined (test)',
                    ]));
                }
            };
        });

        $user = User::factory()->create(['role' => UserRole::Student]);
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => 1000]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/wallet/deposit', [
            'amount' => 20000,
            'idempotency_key' => 'fail-key',
        ])->assertStatus(422);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 1000,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'idempotency_key' => 'fail-key',
            'status' => 'failed',
        ]);
    }

    public function test_guest_cannot_deposit(): void
    {
        $this->postJson('/api/v1/wallet/deposit', [
            'amount' => 1000,
        ])->assertUnauthorized();
    }
}
