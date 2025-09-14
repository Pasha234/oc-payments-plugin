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
            $table->removeColumn('deleted_at');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('palpalych_payments_subscriptions')) {
            return;
        }

        Schema::table('palpalych_payments_subscriptions', function (Blueprint $table) {
            $table->timestamp('deleted_at');
        });
    }
};
