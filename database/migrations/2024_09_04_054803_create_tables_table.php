<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->string('elementId', 50);
            $table->string('name', 500);
            $table->integer('table_number');
            $table->string('type')->default('table');
            $table->longText('classes')->nullable();
            $table->integer('locationX');
            $table->integer('locationY');
            $table->integer('width');
            $table->integer('height');
            $table->string('relatedArea', 500)->nullable();
            $table->string('color', 100)->nullable();
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
        Schema::dropIfExists('tables');
    }
}
