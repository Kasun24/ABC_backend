<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('orders_id');
            $table->foreign('orders_id')->references('id')->on('orders');
            $table->foreignId('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('customer_devices');
            $table->double('amount', 8, 2);
            $table->enum('payment_type', ['cod','paypal','ecCard','mollie','points','card_handled_by_waiter','cash_handled_by_waiter']);
            $table->bigInteger('waiter_id')->nullable();
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
        Schema::dropIfExists('order_payments');
    }
}
