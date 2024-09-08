<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemTopingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_item_topings', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('order_items_id');
            $table->foreign('order_items_id')->references('id')->on('order_items');
            $table->foreignId('toping_id');
            $table->foreign('toping_id')->references('id')->on('product_size_scenario_topings');
            $table->integer('count');
            $table->double('price', 8, 2);
            $table->double('total', 8, 2);
            $table->double('discounted_total', 8, 2)->default(0.00);
            $table->longText('discount_details')->nullable();
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
        Schema::dropIfExists('order_item_topings');
    }
}
