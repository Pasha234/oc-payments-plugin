<?php namespace PalPalych\Payments\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreatePaymentMethodsTable Migration
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
        Schema::create('palpalych_payments_payment_methods', function(Blueprint $table) {
            $table->id();

            $usersTable = (new \RainLab\User\Models\User())->getTable();
            $usersPkType = Schema::getColumnType($usersTable, 'id');

            if ($usersPkType === 'integer') {
                $table->integer('user_id')->unsigned()->nullable();
                $table->foreign('user_id')
                  ->references('id')
                  ->on($usersTable)
                  ->onDelete('set null');
            } elseif ($usersPkType === 'bigint') {
                $table->foreignId('user_id')->constrained('users')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            } else {
                $table->foreignId('user_id')->constrained('users')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            }

            $table->string('transaction_id')->nullable();
            $table->text('payment_data')->nullable();
            $table->text('payment_response')->nullable();
            $table->string('payment_token')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('palpalych_payments_payment_methods');
    }
};
