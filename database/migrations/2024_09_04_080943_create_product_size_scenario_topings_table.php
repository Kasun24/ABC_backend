<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductSizeScenarioTopingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_size_scenario_topings', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('product_size_scenarios_id');
            $table->foreign('product_size_scenarios_id')->references('id')->on('product_size_scenarios');
            $table->foreignId('mcs_topings_id');
            $table->foreign('mcs_topings_id')->references('id')->on('menu_category_senario_topings');
            $table->foreignId('mcst_prices_id');
            $table->foreign('mcst_prices_id')->references('id')->on('mcst_prices');
            $table->string('name',191);
            $table->double('pickup', 8, 2);
            $table->double('delivery', 8, 2);
            $table->double('dine_in', 8, 2);
            $table->enum('status', ['true', 'false']);
            $table->enum('is_mandatory', ['true', 'false']);
            $table->integer('position');
            $table->bigInteger('tax')->nullable()->default(null);
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
        Schema::dropIfExists('product_size_scenario_topings');
    }
}
