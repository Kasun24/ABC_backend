<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSizeMenuCategoryScenariosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_size_menu_category_scenarios', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('psmc_id')->nullable();
            $table->foreign('psmc_id')->references('id')->on('product_size_menu_categories');
            $table->foreignId('products_id')->nullable();
            $table->foreign('products_id')->references('id')->on('products');
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
        Schema::dropIfExists('product_size_menu_category_scenarios');
    }
}
