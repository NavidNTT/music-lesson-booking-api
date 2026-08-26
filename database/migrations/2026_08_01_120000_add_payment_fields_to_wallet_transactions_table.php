<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->after('description');
            $table->string('gateway', 50)->nullable()->after('idempotency_key');
            $table->string('external_transaction_id', 100)->nullable()->after('gateway');
            $table->string('currency', 3)->nullable()->default('IRR')->after('external_transaction_id');
            $table->text('failure_reason')->nullable()->after('currency');

            // One logical deposit attempt per wallet + key.
            $table->unique(
                ['wallet_id', 'idempotency_key'],
                'wallet_transactions_wallet_idempotency_unique'
            );

            // Prevent reusing the same PSP reference twice.
            $table->unique(
                ['gateway', 'external_transaction_id'],
                'wallet_transactions_gateway_external_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropUnique('wallet_transactions_wallet_idempotency_unique');
            $table->dropUnique('wallet_transactions_gateway_external_unique');

            $table->dropColumn([
                'idempotency_key',
                'gateway',
                'external_transaction_id',
                'currency',
                'failure_reason',
            ]);
        });
    }
};
