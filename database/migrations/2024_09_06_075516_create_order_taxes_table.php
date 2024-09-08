<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_taxes', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('orders_id');
            $table->foreign('orders_id')->references('id')->on('orders');
            $table->foreignId('taxes_id');
            $table->foreign('taxes_id')->references('id')->on('taxes');
            $table->string('type',191)->nullable();
            $table->string('tax_type',191)->nullable();
            $table->double('amount', 8, 2);
            $table->double('total_amount', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_taxes');
    }
}
