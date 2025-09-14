<?php

namespace PalPalych\Payments\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use PalPalych\Payments\Models\Payment;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('palpalych_payments_payments')) {
            return;
        }

        Schema::table('palpalych_payments_payments', function (Blueprint $table) {
            $table->dropForeignIdFor(Payment::class, 'payment_method_id');

            $table->foreign('payment_method_id')
                ->references('id')
                ->on('palpalych_payments_payment_methods')
                ->onDelete('SET NULL');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('palpalych_payments_payments')) {
            return;
        }

        Schema::table('palpalych_payments_payments', function (Blueprint $table) {
            $table->dropForeignIdFor(Payment::class, 'payment_method_id');

            $table->foreign('payment_method_id')
                ->references('id')
                ->on('palpalych_payments_payment_methods')
                ->onDelete('RESTRICT');
        });
    }
};
