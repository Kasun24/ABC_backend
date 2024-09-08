<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuCategorySenariosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_category_senarios', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('menu_categories_id');
            $table->foreign('menu_categories_id')->references('id')->on('menu_categories');
            $table->foreignId('toping_scenarios_id');
            $table->foreign('toping_scenarios_id')->references('id')->on('toping_scenarios');
            $table->string('name',191);
            $table->integer('position');
            $table->enum('btn_type', ['add-on', 'optional']);
            $table->timestamps();
            $table->softDeletes();
            $table->bigInteger('topping_tax')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_category_senarios');
    }
}
