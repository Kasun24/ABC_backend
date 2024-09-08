<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name',191)->nullable();
            $table->string('last_name',191)->nullable();
            $table->string('email',191)->nullable();
            $table->string('password',191)->nullable();
            $table->string('mobile_number',191)->nullable();
            $table->enum('type', ['apple','facebook', 'google','guest','registered'])->default('guest');
            $table->enum('status', ['true', 'false']);
            $table->enum('is_newsalert', ['true', 'false'])->default('false');
            $table->longText('fp_token')->nullable();
            $table->longText('user_id')->nullable();
            $table->longText('points')->nullable();
            $table->longText('otp_delete')->nullable();
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
        Schema::dropIfExists('customers');
    }
}
