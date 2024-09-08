<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_item_discounts', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('order_items_id');
            $table->foreign('order_items_id')->references('id')->on('order_items');
            $table->foreignId('discounts_id')->nullable();
            $table->foreign('discounts_id')->references('id')->on('discounts');
            $table->string('type',191)->nullable();
            $table->double('amount', 8, 2);
            $table->double('total_amount', 8, 2);
            $table->enum('is_anytime_discount', ['true','false']);
            $table->enum('is_daily_discount', ['true','false']);
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
        Schema::dropIfExists('order_item_discounts');
    }
}
