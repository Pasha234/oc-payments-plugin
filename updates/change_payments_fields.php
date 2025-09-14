<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use PalPalych\Payments\Models\Payment;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('palpalych_payments_payments')) {
            return;
        }

        Schema::table('palpalych_payments_payments', function (Blueprint $table) {
            $table->renameColumn('transaction_id', 'idempotence_key');
            $table->renameColumn('payment_data', 'gateway_request');
            $table->renameColumn('payment_response', 'gateway_response');
            $table->renameColumn('payment_token', 'gateway_id');

            $table->softDeletes();

            $table->foreignId('payment_method_id')
                ->nullable()
                ->constrained('palpalych_payments_payment_methods');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('palpalych_payments_payments')) {
            return;
        }

        Schema::table('palpalych_payments_payments', function (Blueprint $table) {
            $table->renameColumn('idempotence_key', 'transaction_id');
            $table->renameColumn('gateway_request', 'payment_data');
            $table->renameColumn('gateway_response', 'payment_response');
            $table->renameColumn('gateway_id', 'payment_token');

            $table->dropSoftDeletes();

            $table->dropColumn('payment_method_id');
            $table->dropForeignIdFor(Payment::class, 'payment_method_id');
        });
    }
};
