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
