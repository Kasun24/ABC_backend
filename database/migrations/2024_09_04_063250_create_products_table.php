<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreignId('menu_categories_id')->nullable();
            $table->foreign('menu_categories_id')->references('id')->on('menu_categories');
            $table->string('name',191);
            $table->enum('status', ['true', 'false']);
            $table->enum('type', ['toping', 'dish']);
            $table->enum('is_customise', ['true', 'false'])->nullable();
            $table->enum('is_size', ['true', 'false'])->nullable();
            $table->enum('is_combo', ['true', 'false'])->nullable();
            $table->longText('tax')->nullable();
            $table->longText('alagic_ids')->nullable();
            $table->longText('additive_ids')->nullable();
            $table->double('pickup', 8, 2)->nullable();
            $table->double('delivery', 8, 2)->nullable();
            $table->double('dine_in', 8, 2)->nullable();
            $table->double('points_per_dish', 8, 2)->nullable();
            $table->string('dish_number',191)->nullable();
            $table->longText('description')->nullable();
            $table->longText('cross_selling_products')->nullable();
            $table->enum('is_cross_selling_products', ['true', 'false'])->nullable();
            $table->longText('toping_scenario_ids')->nullable();
            $table->longText('menu_categories_ids')->nullable();
            $table->integer('position')->nullable();
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
        Schema::dropIfExists('products');
    }
}
