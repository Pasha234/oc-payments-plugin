<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;


return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('palpalych_payments_subscriptions')) {
            return;
        }

        Schema::table('palpalych_payments_subscriptions', function (Blueprint $table) {
            $table->unique('user_id', 'palpalych_payments_subscriptions_user_id_unique');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('palpalych_payments_subscriptions')) {
            return;
        }

        Schema::table('palpalych_payments_subscriptions', function (Blueprint $table) {
            $table->dropUnique('palpalych_payments_subscriptions_user_id_unique');
        });
    }
};
