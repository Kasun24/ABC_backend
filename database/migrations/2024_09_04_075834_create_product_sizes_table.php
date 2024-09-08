<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSizesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_sizes', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('products_id');
            $table->foreign('products_id')->references('id')->on('products');
            $table->string('name',191);
            $table->enum('status', ['true', 'false']);
            $table->double('pickup', 8, 2);
            $table->double('delivery', 8, 2);
            $table->double('dine_in', 8, 2);
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
        Schema::dropIfExists('product_sizes');
    }
}
