<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->string('name',191);
            $table->string('description',291)->nullable();
            $table->longText('visibility');
            $table->enum('status', ['true', 'false']);
            $table->integer('position');
            $table->enum('is_promotion', ['true', 'false']);
            $table->enum('discounts_disabled', ['true', 'false']);
            $table->enum('disabled_for_pickup', ['true', 'false']);
            $table->enum('disabled_for_dine_in', ['true', 'false']);
            $table->enum('disabled_for_delivery', ['true', 'false']);
            $table->enum('exclude_min_order_amount_cal', ['true', 'false']);
            $table->longText('tax')->nullable();
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
        Schema::dropIfExists('menu_categories');
    }
}
