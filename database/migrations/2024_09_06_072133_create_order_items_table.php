<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('customer_device_id')->nullable();
            $table->foreign('customer_device_id')->references('id')->on('customer_devices');
            $table->foreignId('orders_id');
            $table->foreign('orders_id')->references('id')->on('orders');
            $table->foreignId('dish_id')->nullable();
            $table->foreign('dish_id')->references('id')->on('products');
            $table->foreignId('size_id')->nullable();
            $table->foreign('size_id')->references('id')->on('product_sizes');
            $table->integer('count');
            $table->double('dish_price', 8, 2);
            $table->double('topping_price', 8, 2);
            $table->double('total', 8, 2);
            $table->double('net_total', 8, 2);
            $table->double('total_discount', 8, 2);
            $table->double('total_tax_inclusive', 8, 2);
            $table->double('total_tax_exclusive', 8, 2);
            $table->double('gross_without_tax_price', 8, 2);
            $table->double('gross_total', 8, 2);
            $table->double('gross_total_with_discount', 8, 2);
            $table->string('comment',191)->nullable();
            $table->double('points_per_dish', 8, 2)->nullable();
            $table->enum('status',['processing','completed'])->default('processing')->nullable();
            $table->enum('is_winorder',['true','false'])->default('false')->nullable();
            $table->string('badge_id',255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
