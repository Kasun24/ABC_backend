<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMcstPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mcst_prices', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('menu_category_senario_topings_id');
            $table->foreign('menu_category_senario_topings_id')->references('id')->on('menu_category_senario_topings');
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
        Schema::dropIfExists('mcst_prices');
    }
}
