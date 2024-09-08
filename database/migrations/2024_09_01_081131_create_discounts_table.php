<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->enum('type', ['NDVA', 'TLD','CC']);
            $table->string('title',191);
            $table->string('code',191)->nullable();
            $table->string('description',500);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('apply_as', ['percentage', 'fixed']);
            $table->double('amount', 8, 2);
            $table->longText('apply_for')->nullable();
            $table->longText('menu_categories')->nullable();
            $table->integer('number_of_time')->nullable();
            $table->enum('status', ['true', 'false']);
            $table->enum('apply_platform', ['both','web', 'app']);
            $table->enum('is_available_on_normal_discount', ['true', 'false']);
            $table->enum('one_time_per_user', ['true','false'])->nullable();
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
        Schema::dropIfExists('discounts');
    }
}
