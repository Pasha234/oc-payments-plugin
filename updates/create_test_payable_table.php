<?php namespace PalPalych\Payments\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('palpalych_payments_test_payable', function(Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->boolean('paid')->default(false);
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('palpalych_payments_test_payable');
    }
};
