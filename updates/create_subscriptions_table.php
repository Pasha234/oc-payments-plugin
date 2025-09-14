<?php namespace PalPalych\Payments\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateSubscriptionsTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('palpalych_payments_subscriptions', function(Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->tinyInteger('type')->default(1);
            $table->boolean('is_recurring')->default(true);
            $table->boolean('paid')->default(false);
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('palpalych_payments_subscriptions');
    }
};
