<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlagicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alagics', function (Blueprint $table) {
            $table->id();
            $table->string('letter',191);
            $table->longText('description');
            $table->enum('status', ['true', 'false']);
            $table->enum('type', ['additive', 'alagic']);
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
        Schema::dropIfExists('alagics');
    }
}
