<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('gm_id')->nullable();
            $table->foreignId('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreignId('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('table_orders_id')->nullable();
            //Pyment
            $table->enum('payment_type', ['cod', 'paypal', 'ecCard', 'mollie', 'points', 'card_handled_by_staff', 'cash_handled_by_staff']);
            $table->string('payment_id', 191);
            $table->longText('payment_data')->nullable();
            //Order Status
            $table->enum('status', ['processing', 'cancelled', 'paid', 'accepted', 'rejected', 'completed']);
            //Order
            $table->enum('order_delivery_type', ['pickup', 'delivery', 'dine_in']);
            $table->enum('order_from', ['web', 'app']);
            //Delivery Details
            $table->string('delivery_address', 191)->nullable();
            $table->string('delivery_longitude', 191)->nullable();
            $table->string('delivery_latitude', 191)->nullable();
            $table->enum('delivery_status', ['pending', 'accepted', 'in_progress', 'completed'])->nullable();
            $table->string('delivery_time', 191)->nullable();
            $table->string('delivery_time_resturent', 191)->nullable();
            //Order Details
            $table->string('name', 191)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('mobile_number', 191)->nullable();
            $table->longText('backyard')->nullable();
            $table->longText('special_note')->nullable();
            $table->longText('remarks')->nullable();
            //Amouts
            $table->double('total_tax_inclusive', 8, 2);
            $table->double('total_tax_exclusive', 8, 2);
            $table->double('net_total_without_tax', 8, 2);
            $table->double('net_total', 8, 2);
            $table->double('total_discount', 8, 2);
            $table->double('total_with_discount_price', 8, 2);
            $table->double('delivery_tax_inclusive', 8, 2);
            $table->double('delivery_tax_exclusive', 8, 2);
            $table->double('delivery_cost', 8, 2);
            $table->double('gross_total', 8, 2);
            //Language
            $table->string('language', 191)->nullable();
            //Device ID
            $table->string('device_id', 191)->nullable();
            //Review
            $table->longText('review')->nullable();
            $table->integer('order_review_stars')->nullable();
            $table->integer('order_review_delivery_type_stars')->nullable();
            //Order Action Date
            $table->string('order_action_date', 191)->nullable();
            //Order completed date
            $table->string('order_completed_date', 191)->nullable();
            //is_winorder
            $table->enum('is_winorder', ['true', 'false'])->default('false');
            //points
            $table->double('points', 8, 2)->nullable();
            //payment_points
            $table->double('payment_points', 8, 2)->nullable();

            // Sides
            $table->string('sides_remote_order_id', 250)->nullable();
            $table->enum('sides_order_status', ['ACCEPTED', 'BEGIN_PREPARING', 'END_PREPARING', 'DRIVER_PICKUP', 'READY_FOR_TAKEAWAY', 'DELIVERED', 'ERROR', 'DISPATCH_ERROR', 'CANCELED'])->nullable();
            $table->enum('sides_sync', ['true', 'false'])->default('false');

            // Transaction fee
            $table->double('transaction_fee', 8, 2)->default(0.00);
            $table->double('transaction_fee_tax', 8, 2)->default(0.00);
            $table->longText('transaction_fee_tax_details')->nullable();
            $table->string('transaction_fee_type', 191)->nullable();

            // Coupon details
            $table->longText('coupon_details')->nullable();
            $table->double('coupon_discount')->nullable()->default(null);

            $table->double('fixed_transaction_fee', 8, 2)->default(0.00);

            $table->double('total_topping_inclusive_tax_amount', 8, 2)->default(0.00);
            $table->double('total_topping_exclusive_tax_amount', 8, 2)->default(0.00);

            //DateTime
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
        Schema::dropIfExists('orders');
    }
}
