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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_product_id')->nullable()->after('sku');
            $table->string('odoo_product_sku')->nullable()->after('odoo_product_id');
            $table->string('odoo_product_name')->nullable()->after('odoo_product_sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'odoo_product_id',
                'odoo_product_sku',
                'odoo_product_name',
            ]);
        });
    }
};