<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_discounts', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('orders_id');
            $table->foreign('orders_id')->references('id')->on('orders');
            $table->foreignId('discounts_id')->nullable();
            $table->foreign('discounts_id')->references('id')->on('discounts');
            $table->string('coupon_code',191)->nullable();
            $table->string('type',191);
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
        Schema::dropIfExists('order_discounts');
    }
}
