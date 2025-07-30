<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('order_id')->unique();
            $table->string('customer_phone');
            $table->string('building')->nullable();
            $table->timestamp('order_time')->nullable();
            $table->timestamp('delivery_time')->nullable();
            $table->timestamp('delivered_on')->nullable();
            $table->string('agent_number')->nullable();
            $table->string('message_id')->nullable();
            $table->string('status')->default("pending");
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
