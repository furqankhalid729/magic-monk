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
        Schema::table('orders', function (Blueprint $table) {
            $table->string("payment_method")->nullable()->after('status');
            $table->string("payment_link")->nullable()->after('payment_method');
            $table->string("payment_qr_code")->nullable()->after('payment_link');
            $table->string("payment_status")->nullable()->after('payment_qr_code');
            $table->string("delivery_instructions")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_link',
                'payment_qr_code',
                'payment_status',
                'delivery_instructions'
            ]);
        });
    }
};
