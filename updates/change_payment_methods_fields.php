<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('palpalych_payments_payment_methods')) {
            return;
        }

        Schema::table('palpalych_payments_payment_methods', function (Blueprint $table) {
            $table->renameColumn('transaction_id', 'idempotence_key');
            $table->renameColumn('payment_data', 'gateway_request');
            $table->renameColumn('payment_response', 'gateway_response');
            $table->renameColumn('payment_token', 'gateway_id');

            $table->string('card_type')->nullable();
            $table->string('last4')->nullable();
            $table->string('expiry_year')->nullable();
            $table->string('expiry_month')->nullable();
        });
    }

    public function down()
    {
        if (!Schema::hasTable('palpalych_payments_payment_methods')) {
            return;
        }

        Schema::table('palpalych_payments_payment_methods', function (Blueprint $table) {
            $table->renameColumn('idempotence_key', 'transaction_id');
            $table->renameColumn('gateway_request', 'payment_data');
            $table->renameColumn('gateway_response', 'payment_response');
            $table->renameColumn('gateway_id', 'payment_token');

            $table->dropColumn('card_type');
            $table->dropColumn('last4');
            $table->dropColumn('expiry_year');
            $table->dropColumn('expiry_month');
        });
    }
};
