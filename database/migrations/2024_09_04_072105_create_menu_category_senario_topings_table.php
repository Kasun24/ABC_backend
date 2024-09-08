<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuCategorySenarioTopingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_category_senario_topings', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('menu_category_senarios_id');
            $table->foreign('menu_category_senarios_id')->references('id')->on('menu_category_senarios');
            $table->foreignId('products_id');
            $table->foreign('products_id')->references('id')->on('products');
            $table->string('name',191);
            $table->enum('status', ['true', 'false']);
            $table->integer('position');
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
        Schema::dropIfExists('menu_category_senario_topings');
    }
}
