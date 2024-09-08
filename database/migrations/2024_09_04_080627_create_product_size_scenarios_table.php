<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSizeScenariosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_size_scenarios', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('products_id')->nullable();
            $table->foreign('products_id')->references('id')->on('products');
            $table->foreignId('product_sizes_id')->nullable();
            $table->foreign('product_sizes_id')->references('id')->on('product_sizes');
            $table->foreignId('menu_category_senarios_id');
            $table->foreign('menu_category_senarios_id')->references('id')->on('menu_category_senarios');
            $table->string('name',191);
            $table->enum('status', ['true', 'false']);
            $table->enum('btn_type', ['add-on', 'optional']);
            $table->integer('position');
            $table->enum('is_mandatory', ['true', 'false']);
            $table->integer('min_toping_count');
            $table->integer('max_toping_count');
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
        Schema::dropIfExists('product_size_scenarios');
    }
}
